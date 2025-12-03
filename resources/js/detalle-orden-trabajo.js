/**
 * Page Detalle Orden Trabajo
 */

'use strict';

import Dropzone from 'dropzone';

// Desactivar autodiscover para evitar que Dropzone intente adjuntarse automáticamente
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function () {
  const ordenId = window.ordenId;
  const userPermissions = window.userPermissions || {}; // Get permissions injected from view

  const dropzoneEl = document.getElementById('dropzone-fotografias');
  const btnCompletarEtapa = document.getElementById('btn-completar-etapa');
  const modalSubirFotos = document.getElementById('modalSubirFotos');
  let myDropzone = null;

  // Check permissions for initial buttons state
  if (!userPermissions.canAdvanceStage) {
      if (btnCompletarEtapa) btnCompletarEtapa.disabled = true;
      const allCompleteButtons = document.querySelectorAll('[id^="btn-completar-"]');
      allCompleteButtons.forEach(btn => btn.disabled = true);
  }

  // Initialize Select2 inside modals
  const select2Elements = $('.select2');
  if (select2Elements.length) {
    select2Elements.each(function () {
      const $this = $(this);
      const parent = $this.closest('.modal');
      $this.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Seleccionar opción',
        dropdownParent: parent.length ? parent : $(document.body)
      });
    });
  }

  // Implementación personalizada de uploadFiles para evitar la simulación del template
  const realUploadFiles = function(files) {
    const self = this;
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const formData = new FormData();
      
      // Agregar el archivo
      formData.append('file', file);
      
      // Configurar XHR
      const xhr = new XMLHttpRequest();
      xhr.open('POST', this.options.url, true);
      
      // Headers
      xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
      xhr.setRequestHeader('Accept', 'application/json');
      
      // Progress
      xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
          const progress = (e.loaded / e.total) * 100;
          self.emit('uploadprogress', file, progress, e.loaded);
        }
      };
      
      // Load
      xhr.onload = function(e) {
        if (xhr.status >= 200 && xhr.status < 300) {
          let response;
          try {
              response = JSON.parse(xhr.responseText);
          } catch(err) {
              response = xhr.responseText;
          }
          
          file.status = Dropzone.SUCCESS;
          self.emit('success', file, response, e);
          self.emit('complete', file);
          self.processQueue();
        } else {
          file.status = Dropzone.ERROR;
          let response;
          try {
              response = JSON.parse(xhr.responseText);
          } catch(err) {
              response = xhr.responseText;
          }
          const message = response.message || (typeof response === 'string' ? response : "Error de subida");
          self.emit('error', file, message, xhr);
          self.emit('complete', file);
          self.processQueue();
        }
      };
      
      // Error
      xhr.onerror = function(e) {
          file.status = Dropzone.ERROR;
          self.emit('error', file, "Error de red", xhr);
          self.emit('complete', file);
          self.processQueue();
      };
      
      xhr.send(formData);
    }
  };

  // Configurar Dropzone si existe (ahora está dentro de un modal)
  if (dropzoneEl) {
    const previewTemplate = `<div class="dz-preview dz-file-preview">
      <div class="dz-details">
        <div class="dz-thumbnail">
          <img data-dz-thumbnail>
          <span class="dz-nopreview">No preview</span>
          <div class="dz-success-mark"></div>
          <div class="dz-error-mark"></div>
          <div class="dz-error-message"><span data-dz-errormessage></span></div>
          <div class="progress">
            <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
          </div>
        </div>
        <div class="dz-filename" data-dz-name></div>
        <div class="dz-size" data-dz-size></div>
      </div>
    </div>`;

    // Inicializar Dropzone
    myDropzone = new Dropzone(dropzoneEl, {
      previewTemplate: previewTemplate,
      parallelUploads: 1,
      maxFilesize: 10, // 10MB
      acceptedFiles: 'image/jpeg,image/jpg,image/png,image/gif,image/webp',
      addRemoveLinks: true,
      url: `${baseUrl}ordenes-trabajo/${ordenId}/fotografias`,
      method: 'post',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        Accept: 'application/json'
      },
      autoProcessQueue: true,
      init: function () {
        const dropzone = this;
        
        // Sobrescribir el método uploadFiles con nuestra implementación real
        dropzone.uploadFiles = realUploadFiles;

        // Asegurar que cuando se agregue un archivo se procese (fix para modales)
        dropzone.on('addedfile', function (file) {
          setTimeout(() => {
            if (dropzone.getQueuedFiles().length > 0) {
              dropzone.processQueue();
            }
          }, 10);
        });

        // Cuando se sube exitosamente una foto
        dropzone.on('success', function (file, response, xhr) {
          let responseData = response;

          if (responseData && responseData.success && responseData.fotografia) {
            // Guardar el ID de la foto en el objeto file para poder eliminarlo después
            file.fotoId = responseData.fotografia.id;
            
            // Agregar la foto a la galería
            agregarFotoAGaleria(responseData.fotografia);
            actualizarContador();
            habilitarBotonCompletar();
            
          }
        });

        // Cuando hay un error (manejado por nuestra implementación custom de uploadFiles)
        dropzone.on('error', function (file, errorMessage, xhr) {
           // El mensaje ya viene procesado por nuestra función realUploadFiles o Dropzone default
           // No necesitamos hacer mucho aquí salvo quizás mostrar alerta si se desea
        });

        // Remover archivo del servidor cuando se elimina del dropzone
        dropzone.on('removedfile', function (file) {
          // Solo visual
        });
      }
    });
  }

  // Función para agregar foto a la galería
  function agregarFotoAGaleria(foto) {
    const container = document.getElementById('fotografias-container');
    const colVacio = container.querySelector('.col-12 .text-center');
    
    if (colVacio) {
      colVacio.remove();
    }

    // Conditional delete button
    const deleteButton = userPermissions.canManagePhotos 
        ? `<button type="button" class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 m-2 eliminar-foto" data-foto-id="${foto.id}" title="Eliminar"><i class="icon-base ti tabler-trash"></i></button>`
        : '';

    const fotoHtml = `
      <div class="col-6 col-md-4 col-lg-3" data-foto-id="${foto.id}">
        <div class="card h-100">
          <div class="card-img-wrapper position-relative">
            <img src="${foto.ruta_archivo}" class="card-img-top" alt="${foto.nombre_archivo}" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="abrirImagen('${foto.ruta_archivo}', '${foto.nombre_archivo}')">
            ${deleteButton}
          </div>
          <div class="card-body p-3">
            <p class="card-text small text-muted mb-0" title="${foto.nombre_archivo}">
              ${foto.nombre_archivo.length > 20 ? foto.nombre_archivo.substring(0, 20) + '...' : foto.nombre_archivo}
            </p>
            <small class="text-muted">${new Date().toLocaleDateString('es-ES')} ${new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}</small>
          </div>
        </div>
      </div>
    `;

    container.insertAdjacentHTML('beforeend', fotoHtml);
  }

  // Función para actualizar contador
  function actualizarContador() {
    // Actualizar badge en el modal (si hubiera) y en el botón del timeline
    const totalFotos = document.querySelectorAll('#fotografias-container [data-foto-id]').length;
    
    const badgeTimeline = document.getElementById('badge-contador-fotos');
    if (badgeTimeline) {
      badgeTimeline.textContent = totalFotos;
    }
  }

  // Función para habilitar/deshabilitar botón completar
  function habilitarBotonCompletar() {
    if (btnCompletarEtapa) {
        if (!userPermissions.canAdvanceStage) {
            btnCompletarEtapa.disabled = true;
            return;
        }
        const totalFotos = document.querySelectorAll('#fotografias-container [data-foto-id]').length;
        btnCompletarEtapa.disabled = totalFotos === 0;
    }
  }

  // Función para eliminar foto
  function eliminarFoto(fotoId) {
    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        fetch(`${baseUrl}ordenes-trabajo/${ordenId}/fotografias/${fotoId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
          }
        })
          .then(response => {
            if (response.ok) {
              // Remover de la galería
              const fotoElement = document.querySelector(`[data-foto-id="${fotoId}"]`);
              if (fotoElement) {
                fotoElement.remove();
              }

              actualizarContador();
              habilitarBotonCompletar();

              // Si no quedan fotos, mostrar mensaje
              const totalFotos = document.querySelectorAll('#fotografias-container [data-foto-id]').length;
              if (totalFotos === 0) {
                const container = document.getElementById('fotografias-container');
                container.innerHTML = `
                  <div class="col-12">
                    <div class="text-center py-6">
                      <i class="icon-base ti tabler-photo-off icon-4x text-muted mb-3"></i>
                      <p class="text-muted mb-0">No hay fotografías cargadas aún</p>
                    </div>
                  </div>
                `;
              }

              Swal.fire({
                icon: 'success',
                title: '¡Eliminada!',
                text: 'La fotografía ha sido eliminada.',
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
            } else {
              throw new Error('Error al eliminar');
            }
          })
          .catch(error => {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'No se pudo eliminar la fotografía.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          });
      }
    });
  }

  // Event listener para botones de eliminar
  document.addEventListener('click', function (e) {
    if (e.target.closest('.eliminar-foto')) {
      const btn = e.target.closest('.eliminar-foto');
      const fotoId = btn.dataset.fotoId;
      eliminarFoto(fotoId);
    }
  });

  // Limpiar Dropzone cuando se cierra el modal
  if (modalSubirFotos && myDropzone) {
    modalSubirFotos.addEventListener('hidden.bs.modal', function () {
      myDropzone.removeAllFiles();
    });
  }

  // Event listener para completar etapa (Toma de fotografías)
  if (btnCompletarEtapa) {
    btnCompletarEtapa.addEventListener('click', function () {
      Swal.fire({
        title: '¿Completar etapa?',
        text: '¿Estás seguro de que deseas avanzar a la siguiente etapa?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, avanzar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-success me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.value) {
          // Deshabilitar botón mientras se procesa
          btnCompletarEtapa.disabled = true;
          btnCompletarEtapa.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

          fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Content-Type': 'application/json'
            }
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: '¡Éxito!',
                  text: data.message || 'Etapa completada exitosamente',
                  customClass: {
                    confirmButton: 'btn btn-success'
                  }
                }).then(() => {
                  // Recargar la página para mostrar la nueva etapa
                  window.location.reload();
                });
              } else {
                throw new Error(data.error || 'Error al avanzar etapa');
              }
            })
            .catch(error => {
              btnCompletarEtapa.disabled = false;
              btnCompletarEtapa.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i>Completar Etapa y Avanzar a Diagnóstico';
              
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
            });
        }
      });
    });
  }

  // ---------------------------------------------------------
  // Lógica para la etapa de Diagnóstico
  // ---------------------------------------------------------
  const btnGuardarDiagnostico = document.getElementById('btn-guardar-diagnostico');
  const btnCompletarDiagnostico = document.getElementById('btn-completar-diagnostico');
  const formDiagnostico = document.getElementById('form-diagnostico');

  // Función para obtener datos del formulario (incluyendo select2)
  function getFormData(form) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    
    // Asegurarse de obtener el valor de Select2 si no está en FormData (a veces pasa con jQuery)
    const diagnosticadoPor = $('#diagnosticado_por').val();
    if (diagnosticadoPor) {
        data['diagnosticado_por'] = diagnosticadoPor;
    }
    
    return data;
  }

  if (btnGuardarDiagnostico) {
    btnGuardarDiagnostico.addEventListener('click', function() {
        const data = getFormData(formDiagnostico);

        // Validar campos requeridos
        if (!data.duracion_diagnostico || !data.diagnosticado_por || !data.detalle_diagnostico) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor complete todos los campos del diagnóstico.',
                customClass: { confirmButton: 'btn btn-primary' }
            });
            return;
        }

        btnGuardarDiagnostico.disabled = true;
        btnGuardarDiagnostico.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        fetch(`${baseUrl}ordenes-trabajo/${ordenId}/diagnostico`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            btnGuardarDiagnostico.disabled = false;
            btnGuardarDiagnostico.innerHTML = '<i class="icon-base ti tabler-device-floppy icon-sm me-1"></i> Guardar';
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: data.message,
                    customClass: { confirmButton: 'btn btn-success' }
                });
                
                if (btnCompletarDiagnostico && userPermissions.canAdvanceStage) {
                    btnCompletarDiagnostico.disabled = false;
                }
            } else {
                throw new Error(data.message || 'Error al guardar');
            }
        })
        .catch(error => {
            btnGuardarDiagnostico.disabled = false;
            btnGuardarDiagnostico.innerHTML = '<i class="icon-base ti tabler-device-floppy icon-sm me-1"></i> Guardar';
            
             Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo guardar el diagnóstico.',
                customClass: { confirmButton: 'btn btn-primary' }
            });
        });
    });
  }

  if (btnCompletarDiagnostico) {
    btnCompletarDiagnostico.addEventListener('click', function() {
        Swal.fire({
            title: '¿Completar etapa?',
            text: '¿Estás seguro de que deseas completar el diagnóstico y avanzar a Cotizaciones?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, avanzar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                btnCompletarDiagnostico.disabled = true;
                btnCompletarDiagnostico.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Diagnóstico completado. Avanzando a Cotizaciones.',
                            customClass: { confirmButton: 'btn btn-success' }
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.error || 'Error al avanzar etapa');
                    }
                })
                .catch(error => {
                    btnCompletarDiagnostico.disabled = false;
                    btnCompletarDiagnostico.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
            }
        });
    });
  }

  // ---------------------------------------------------------
  // Lógica para la etapa de Cotizaciones
  // ---------------------------------------------------------
  // Usar document.body para delegar eventos ya que los elementos están en un modal
  const modalCotizaciones = document.getElementById('modalCotizaciones');
  
  // Delegar eventos click dentro del modal para el botón de búsqueda y otros
  if (modalCotizaciones) {
    modalCotizaciones.addEventListener('click', function(e) {
        // Búsqueda de cotización
        const btnBuscar = e.target.closest('#btn-buscar-cotizacion');
        if (btnBuscar) {
            e.preventDefault(); // Prevenir envío de formulario si lo hubiera
            
            const inputNumeroCotizacion = document.getElementById('numero_cotizacion_alegra');
            const resultadoCotizacion = document.getElementById('resultado-cotizacion');
            const cotizacionInfo = document.getElementById('cotizacion-info');
            
            const numero = inputNumeroCotizacion.value.trim();
            if (!numero) return;

            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            resultadoCotizacion.classList.add('d-none');
            // Variable global o en scope superior para almacenar resultado
            window.cotizacionEncontrada = null;

            fetch(`${baseUrl}ordenes-trabajo/buscar-cotizacion-alegra`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ numero_cotizacion: numero })
            })
            .then(response => response.json())
            .then(data => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';

                if (data.success) {
                    window.cotizacionEncontrada = data.data;
                    cotizacionInfo.innerHTML = `<strong>Cotización #${window.cotizacionEncontrada.numero}</strong><br>
                        Fecha: ${window.cotizacionEncontrada.fecha}<br>
                        Cliente: ${window.cotizacionEncontrada.cliente}<br>
                        Total: ${window.cotizacionEncontrada.total}`;
                    resultadoCotizacion.classList.remove('d-none');
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No encontrada',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al buscar la cotización.',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }

        // Agregar cotización
        const btnAgregar = e.target.closest('#btn-agregar-cotizacion');
        if (btnAgregar) {
            if (!window.cotizacionEncontrada) return;

            btnAgregar.disabled = true;
            btnAgregar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

            const inputNumeroCotizacion = document.getElementById('numero_cotizacion_alegra');
            const resultadoCotizacion = document.getElementById('resultado-cotizacion');
            const tabla = document.getElementById('tabla-cotizaciones');
            const noCotizacionesRow = document.getElementById('no-cotizaciones');
            const btnCompletar = document.getElementById('btn-completar-cotizaciones');

            fetch(`${baseUrl}ordenes-trabajo/${ordenId}/cotizaciones`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    alegra_id: window.cotizacionEncontrada.id,
                    numero_cotizacion: window.cotizacionEncontrada.numero,
                    cliente_nombre: window.cotizacionEncontrada.cliente,
                    fecha_emision: window.cotizacionEncontrada.fecha,
                    total: window.cotizacionEncontrada.total
                })
            })
            .then(response => response.json())
            .then(data => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';

                if (data.success) {
                    // Limpiar búsqueda
                    inputNumeroCotizacion.value = '';
                    resultadoCotizacion.classList.add('d-none');
                    window.cotizacionEncontrada = null;

                    // Actualizar tabla dinámicamente
                    if (noCotizacionesRow) {
                        noCotizacionesRow.remove();
                    }

                    const newRow = `
                        <tr data-id="${data.cotizacion.id}">
                            <td>${data.cotizacion.numero_cotizacion}</td>
                            <td>${data.cotizacion.cliente_nombre}</td>
                            <td>${data.cotizacion.fecha_emision}</td>
                            <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(data.cotizacion.total)}</td>
                            <td>
                                ${data.cotizacion.ruta_pdf ? 
                                    `<a href="/storage/${data.cotizacion.ruta_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary">
                                        <i class="icon-base ti tabler-file-type-pdf"></i>
                                    </a>` : 
                                    '<span class="text-muted">-</span>'
                                }
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">Sin aprobar</span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-icon btn-success aprobar-cotizacion" title="Aprobar" data-id="${data.cotizacion.id}">
                                        <i class="icon-base ti tabler-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-cotizacion" title="Eliminar" data-id="${data.cotizacion.id}">
                                        <i class="icon-base ti tabler-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tabla.insertAdjacentHTML('beforeend', newRow);

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al agregar la cotización.',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }
        
        // Completar etapa de cotizaciones
        const btnCompletar = e.target.closest('#btn-completar-cotizaciones');
        if (btnCompletar) {
            Swal.fire({
                title: '¿Completar etapa?',
                text: '¿Estás seguro de que deseas avanzar a la etapa de Órdenes de Compra?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, avanzar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-success me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.value) {
                    btnCompletar.disabled = true;
                    btnCompletar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                    fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Etapa completada. Avanzando a Órdenes de Compra.',
                                customClass: { confirmButton: 'btn btn-success' }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error || 'Error al avanzar etapa');
                        }
                    })
                    .catch(error => {
                        btnCompletar.disabled = false;
                        btnCompletar.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
                }
            });
        }
    });
  }

  // Eliminar cotización
  document.addEventListener('click', function(e) {
    if (e.target.closest('.eliminar-cotizacion')) {
        const btn = e.target.closest('.eliminar-cotizacion');
        const id = btn.dataset.id;
        const row = btn.closest('tr');

        Swal.fire({
            title: '¿Eliminar cotización?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/cotizaciones/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.remove();
                        
                        // Verificar si quedan filas
                        const tabla = document.getElementById('tabla-cotizaciones');
                        if (tabla.querySelectorAll('tr').length === 0) {
                            tabla.innerHTML = `
                                <tr id="no-cotizaciones">
                                    <td colspan="7" class="text-center text-muted py-4">No hay cotizaciones agregadas</td>
                                </tr>
                            `;
                        }

                        // Actualizar botón completar si es necesario (deshabilitarlo si se borró la aprobada)
                        // Esto es complejo de saber sin recargar, pero podemos asumir que hay que revisar
                        // O simplemente deshabilitar el botón y obligar a refrescar o re-aprobar otra
                        // Para simplicidad, recargamos si se eliminó una aprobada, o verificamos estado visual
                        const badge = row.querySelector('.bg-label-success');
                        if (badge) {
                             const btnCompletar = document.getElementById('btn-completar-cotizaciones');
                             if(btnCompletar) btnCompletar.disabled = true;
                        }

                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo eliminar la cotización.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
            }
        });
    }
  });

  // Aprobar cotización
  document.addEventListener('click', function(e) {
    if (e.target.closest('.aprobar-cotizacion')) {
        const btn = e.target.closest('.aprobar-cotizacion');
        const id = btn.dataset.id;

        Swal.fire({
            title: '¿Aprobar cotización?',
            text: "Al aprobar esta cotización, las demás quedarán desmarcadas.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-success me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/cotizaciones/${id}/aprobar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar UI sin recargar
                        const allRows = document.querySelectorAll('#tabla-cotizaciones tr');
                        const btnCompletar = document.getElementById('btn-completar-cotizaciones');
                        
                        allRows.forEach(row => {
                            const badgeCell = row.querySelector('td:nth-child(6)');
                            const actionsCell = row.querySelector('td:nth-child(7)');
                            const rowId = row.dataset.id;
                            
                            if (rowId === id) {
                                // Esta es la aprobada
                                badgeCell.innerHTML = '<span class="badge bg-label-success">Aprobada</span>';
                                // Quitar botón aprobar de acciones
                                const approveBtn = actionsCell.querySelector('.aprobar-cotizacion');
                                if (approveBtn) approveBtn.remove();
                            } else {
                                // Las demás
                                badgeCell.innerHTML = '<span class="badge bg-label-secondary">Sin aprobar</span>';
                                // Asegurar que tengan botón aprobar si no lo tienen
                                if (!actionsCell.querySelector('.aprobar-cotizacion')) {
                                    const deleteBtn = actionsCell.querySelector('.eliminar-cotizacion');
                                    const newApproveBtn = document.createElement('button');
                                    newApproveBtn.type = 'button';
                                    newApproveBtn.className = 'btn btn-sm btn-icon btn-success aprobar-cotizacion me-1'; // Agregado margen derecho
                                    newApproveBtn.title = 'Aprobar';
                                    newApproveBtn.dataset.id = rowId;
                                    newApproveBtn.innerHTML = '<i class="icon-base ti tabler-check"></i>';
                                    
                                    // Insertar antes del botón eliminar
                                    if (deleteBtn) {
                                        actionsCell.querySelector('div').insertBefore(newApproveBtn, deleteBtn);
                                    }
                                }
                            }
                        });

                        // Habilitar botón completar etapa si tiene permisos
                        if (btnCompletar && userPermissions.canAdvanceStage) {
                            btnCompletar.disabled = false;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Aprobada',
                            text: 'Cotización aprobada exitosamente.',
                            customClass: { confirmButton: 'btn btn-success' },
                            timer: 1500,
                            showConfirmButton: false
                        });

                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo aprobar la cotización.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
            }
        });
    }
  });

  // ---------------------------------------------------------
  // Lógica para la etapa de Órdenes de Compra
  // ---------------------------------------------------------
  const modalOrdenesCompra = document.getElementById('modalOrdenesCompra');
  
  if (modalOrdenesCompra) {
    modalOrdenesCompra.addEventListener('click', function(e) {
        // Búsqueda de orden de compra
        const btnBuscar = e.target.closest('#btn-buscar-orden-compra');
        if (btnBuscar) {
            e.preventDefault();
            
            const inputNumeroOrden = document.getElementById('numero_orden_compra_alegra');
            const resultadoOrden = document.getElementById('resultado-orden-compra');
            const ordenInfo = document.getElementById('orden-compra-info');
            
            const numero = inputNumeroOrden.value.trim();
            if (!numero) return;

            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            resultadoOrden.classList.add('d-none');
            window.ordenCompraEncontrada = null;

            fetch(`${baseUrl}ordenes-trabajo/buscar-orden-compra-alegra`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ numero_orden: numero })
            })
            .then(response => response.json())
            .then(data => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';

                if (data.success) {
                    window.ordenCompraEncontrada = data.data;
                    ordenInfo.innerHTML = `<strong>Orden de Compra #${window.ordenCompraEncontrada.numero}</strong><br>
                        Fecha: ${window.ordenCompraEncontrada.fecha}<br>
                        Proveedor: ${window.ordenCompraEncontrada.proveedor}<br>
                        Total: ${window.ordenCompraEncontrada.total}`;
                    resultadoOrden.classList.remove('d-none');
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No encontrada',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al buscar la orden de compra.',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }

        // Agregar orden de compra
        const btnAgregar = e.target.closest('#btn-agregar-orden-compra');
        if (btnAgregar) {
            if (!window.ordenCompraEncontrada) return;

            btnAgregar.disabled = true;
            btnAgregar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

            const inputNumeroOrden = document.getElementById('numero_orden_compra_alegra');
            const resultadoOrden = document.getElementById('resultado-orden-compra');
            const tabla = document.getElementById('tabla-ordenes-compra');
            const noOrdenesRow = document.getElementById('no-ordenes-compra');
            const btnCompletar = document.getElementById('btn-completar-ordenes-compra');

            fetch(`${baseUrl}ordenes-trabajo/${ordenId}/ordenes-compra`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    alegra_id: window.ordenCompraEncontrada.id,
                    numero_orden: window.ordenCompraEncontrada.numero,
                    proveedor_nombre: window.ordenCompraEncontrada.proveedor,
                    fecha_emision: window.ordenCompraEncontrada.fecha,
                    total: window.ordenCompraEncontrada.total
                })
            })
            .then(response => response.json())
            .then(data => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';

                if (data.success) {
                    // Limpiar búsqueda
                    inputNumeroOrden.value = '';
                    resultadoOrden.classList.add('d-none');
                    window.ordenCompraEncontrada = null;

                    // Actualizar tabla
                    if (noOrdenesRow) {
                        noOrdenesRow.remove();
                    }

                    const newRow = `
                        <tr data-id="${data.orden_compra.id}">
                            <td>${data.orden_compra.numero_orden}</td>
                            <td>${data.orden_compra.proveedor_nombre}</td>
                            <td>${data.orden_compra.fecha_emision}</td>
                            <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(data.orden_compra.total)}</td>
                            <td>
                                ${data.orden_compra.ruta_pdf ? 
                                    `<a href="/storage/${data.orden_compra.ruta_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary">
                                        <i class="icon-base ti tabler-file-type-pdf"></i>
                                    </a>` : 
                                    '<span class="text-muted">-</span>'
                                }
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-orden-compra" title="Eliminar" data-id="${data.orden_compra.id}">
                                    <i class="icon-base ti tabler-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tabla.insertAdjacentHTML('beforeend', newRow);

                    // Habilitar botón completar
                    if (btnCompletar && userPermissions.canAdvanceStage) {
                        btnCompletar.disabled = false;
                    }

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al agregar la orden de compra.',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }
        
        // Completar etapa de órdenes de compra
        const btnCompletar = e.target.closest('#btn-completar-ordenes-compra');
        if (btnCompletar) {
            Swal.fire({
                title: '¿Completar etapa?',
                text: '¿Estás seguro de que deseas avanzar a la etapa de Entrega de repuestos?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, avanzar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-success me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.value) {
                    btnCompletar.disabled = true;
                    btnCompletar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                    fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Etapa completada. Avanzando a Entrega de repuestos.',
                                customClass: { confirmButton: 'btn btn-success' }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error || 'Error al avanzar etapa');
                        }
                    })
                    .catch(error => {
                        btnCompletar.disabled = false;
                        btnCompletar.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
                }
            });
        }
    });
  }

  // Eliminar orden de compra
  document.addEventListener('click', function(e) {
    if (e.target.closest('.eliminar-orden-compra')) {
        const btn = e.target.closest('.eliminar-orden-compra');
        const id = btn.dataset.id;
        const row = btn.closest('tr');

        Swal.fire({
            title: '¿Eliminar orden de compra?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/ordenes-compra/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.remove();
                        
                        // Verificar si quedan filas
                        const tabla = document.getElementById('tabla-ordenes-compra');
                        if (tabla.querySelectorAll('tr').length === 0) {
                            tabla.innerHTML = `
                                <tr id="no-ordenes-compra">
                                    <td colspan="6" class="text-center text-muted py-4">No hay órdenes de compra agregadas</td>
                                </tr>
                            `;
                            
                            // Deshabilitar botón completar si no quedan órdenes
                            const btnCompletar = document.getElementById('btn-completar-ordenes-compra');
                            if (btnCompletar) {
                                btnCompletar.disabled = true;
                            }
                        }

                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo eliminar la orden de compra.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
            }
        });
    }
  });

  // ---------------------------------------------------------
  // Lógica para la etapa de Entrega de Repuestos
  // ---------------------------------------------------------
  const modalEntregaRepuestos = document.getElementById('modalEntregaRepuestos');
  
  if (modalEntregaRepuestos) {
    const checkRepuestos = document.getElementById('check-repuestos-entregados');
    const checkTiquete = document.getElementById('check-tiquete-impreso');
    const btnImprimir = document.getElementById('btn-imprimir-tiquete');
    const btnCompletarEntrega = document.getElementById('btn-completar-entrega-repuestos');

    // Función para actualizar estado en el servidor
    function actualizarEstadoEntrega() {
        const datos = {
            repuestos_entregados: checkRepuestos.checked,
            tiquete_impreso: checkTiquete.checked
        };

        fetch(`${baseUrl}ordenes-trabajo/${ordenId}/entrega-repuestos`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Habilitar botón completar si ambos checks están true
                if (checkRepuestos.checked && checkTiquete.checked && userPermissions.canAdvanceStage) {
                    btnCompletarEntrega.disabled = false;
                } else {
                    btnCompletarEntrega.disabled = true;
                }
            }
        })
        .catch(error => console.error('Error actualizando estado:', error));
    }

    if (checkRepuestos) {
        checkRepuestos.addEventListener('change', actualizarEstadoEntrega);
    }

    if (btnImprimir) {
        btnImprimir.addEventListener('click', function() {
            // Marcar check de tiquete impreso automáticamente
            if (checkTiquete && !checkTiquete.checked) {
                checkTiquete.checked = true;
                actualizarEstadoEntrega();
            }
        });
    }

    if (btnCompletarEntrega) {
        btnCompletarEntrega.addEventListener('click', function() {
            Swal.fire({
                title: '¿Completar etapa?',
                text: '¿Estás seguro de que deseas avanzar a la etapa de Ejecución?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, avanzar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-success me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.value) {
                    btnCompletarEntrega.disabled = true;
                    btnCompletarEntrega.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                    fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Etapa completada. Avanzando a Ejecución.',
                                customClass: { confirmButton: 'btn btn-success' }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error || 'Error al avanzar etapa');
                        }
                    })
                    .catch(error => {
                        btnCompletarEntrega.disabled = false;
                        btnCompletarEntrega.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
                }
            });
        });
    }
  }

  // ---------------------------------------------------------
  // Lógica para la etapa de Ejecución
  // ---------------------------------------------------------
  const btnCompletarEjecucion = document.getElementById('btn-completar-ejecucion-directo');

  if (btnCompletarEjecucion) {
      btnCompletarEjecucion.addEventListener('click', function() {
          Swal.fire({
              title: '¿Completar ejecución?',
              text: '¿Estás seguro de que deseas completar la ejecución y avanzar a Facturación?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Sí, avanzar',
              cancelButtonText: 'Cancelar',
              customClass: {
                  confirmButton: 'btn btn-success me-3',
                  cancelButton: 'btn btn-label-secondary'
              },
              buttonsStyling: false
          }).then(function(result) {
              if (result.value) {
                  btnCompletarEjecucion.disabled = true;
                  btnCompletarEjecucion.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                  fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                      method: 'POST',
                      headers: {
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                          'Content-Type': 'application/json'
                      }
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          Swal.fire({
                              icon: 'success',
                              title: '¡Éxito!',
                              text: 'Etapa completada. Avanzando a Facturación.',
                              customClass: { confirmButton: 'btn btn-success' }
                          }).then(() => {
                              window.location.reload();
                          });
                      } else {
                          throw new Error(data.error || 'Error al avanzar etapa');
                      }
                  })
                  .catch(error => {
                      btnCompletarEjecucion.disabled = false;
                      btnCompletarEjecucion.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Ejecución';
                      
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: error.message || 'No se pudo avanzar a la siguiente etapa.',
                          customClass: { confirmButton: 'btn btn-primary' }
                      });
                  });
              }
          });
      });
  }

  // ---------------------------------------------------------
  // Lógica para la etapa de Facturación
  // ---------------------------------------------------------
  const modalFacturacion = document.getElementById('modalFacturacion');
  
  if (modalFacturacion) {
    modalFacturacion.addEventListener('click', function(e) {
        // Búsqueda de factura
        const btnBuscar = e.target.closest('#btn-buscar-factura');
        if (btnBuscar) {
            e.preventDefault();
            
            const inputNumeroFactura = document.getElementById('numero_factura_alegra');
            const resultadoFactura = document.getElementById('resultado-factura');
            const facturaInfo = document.getElementById('factura-info');
            
            const numero = inputNumeroFactura.value.trim();
            if (!numero) return;

            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            resultadoFactura.classList.add('d-none');
            window.facturaEncontrada = null;

            fetch(`${baseUrl}ordenes-trabajo/buscar-factura-alegra`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ numero_factura: numero })
            })
            .then(response => response.json())
            .then(data => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';

                if (data.success) {
                    window.facturaEncontrada = data.data;
                    facturaInfo.innerHTML = `<strong>Factura #${window.facturaEncontrada.numero}</strong><br>
                        Fecha: ${window.facturaEncontrada.fecha}<br>
                        Cliente: ${window.facturaEncontrada.cliente}<br>
                        Total: ${window.facturaEncontrada.total}`;
                    resultadoFactura.classList.remove('d-none');
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No encontrada',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al buscar la factura.',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }

        // Agregar factura
        const btnAgregar = e.target.closest('#btn-agregar-factura');
        if (btnAgregar) {
            if (!window.facturaEncontrada) return;

            btnAgregar.disabled = true;
            btnAgregar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

            const inputNumeroFactura = document.getElementById('numero_factura_alegra');
            const resultadoFactura = document.getElementById('resultado-factura');
            const tabla = document.getElementById('tabla-facturas');
            const noFacturasRow = document.getElementById('no-facturas');
            const btnCompletar = document.getElementById('btn-completar-facturacion');

            fetch(`${baseUrl}ordenes-trabajo/${ordenId}/facturas`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    alegra_id: window.facturaEncontrada.id,
                    numero_factura: window.facturaEncontrada.numero,
                    cliente_nombre: window.facturaEncontrada.cliente,
                    fecha_emision: window.facturaEncontrada.fecha,
                    total: window.facturaEncontrada.total
                })
            })
            .then(response => response.json())
            .then(data => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';

                if (data.success) {
                    // Limpiar búsqueda
                    inputNumeroFactura.value = '';
                    resultadoFactura.classList.add('d-none');
                    window.facturaEncontrada = null;

                    // Actualizar tabla
                    if (noFacturasRow) {
                        noFacturasRow.remove();
                    }

                    const newRow = `
                        <tr data-id="${data.factura.id}">
                            <td>${data.factura.numero_factura}</td>
                            <td>${data.factura.cliente_nombre}</td>
                            <td>${data.factura.fecha_emision}</td>
                            <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(data.factura.total)}</td>
                            <td>
                                ${data.factura.ruta_pdf ? 
                                    `<a href="/storage/${data.factura.ruta_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary">
                                        <i class="icon-base ti tabler-file-type-pdf"></i>
                                    </a>` : 
                                    '<span class="text-muted">-</span>'
                                }
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-factura" title="Eliminar" data-id="${data.factura.id}">
                                    <i class="icon-base ti tabler-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tabla.insertAdjacentHTML('beforeend', newRow);

                    // Ocultar formulario de agregar porque solo se permite una
                    const searchContainer = inputNumeroFactura.closest('.mb-4');
                    if (searchContainer) searchContainer.classList.add('d-none');

                    // Habilitar botón completar
                    if (btnCompletar && userPermissions.canAdvanceStage) {
                        btnCompletar.disabled = false;
                    }

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            })
            .catch(error => {
                btnAgregar.disabled = false;
                btnAgregar.innerHTML = '<i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al agregar la factura.', // Improved error message handling
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
        }
        
        // Completar etapa de facturación
        const btnCompletar = e.target.closest('#btn-completar-facturacion');
        if (btnCompletar) {
            Swal.fire({
                title: '¿Completar etapa?',
                text: '¿Estás seguro de que deseas finalizar la orden de trabajo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-success me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.value) {
                    btnCompletar.disabled = true;
                    btnCompletar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

                    fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Orden Finalizada!',
                                text: 'La orden de trabajo ha sido completada exitosamente.',
                                customClass: { confirmButton: 'btn btn-success' }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error || 'Error al avanzar etapa');
                        }
                    })
                    .catch(error => {
                        btnCompletar.disabled = false;
                        btnCompletar.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo finalizar la orden.',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
                }
            });
        }
    });
  }

  // Eliminar factura
  document.addEventListener('click', function(e) {
    if (e.target.closest('.eliminar-factura')) {
        const btn = e.target.closest('.eliminar-factura');
        const id = btn.dataset.id;
        const row = btn.closest('tr');

        Swal.fire({
            title: '¿Eliminar factura?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/facturas/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.remove();
                        
                        // Verificar si quedan filas
                        const tabla = document.getElementById('tabla-facturas');
                        if (tabla.querySelectorAll('tr').length === 0) {
                            tabla.innerHTML = `
                                <tr id="no-facturas">
                                    <td colspan="6" class="text-center text-muted py-4">No hay facturas agregadas</td>
                                </tr>
                            `;
                            
                            // Deshabilitar botón completar si no quedan facturas
                            const btnCompletar = document.getElementById('btn-completar-facturacion');
                            if (btnCompletar) {
                                btnCompletar.disabled = true;
                            }

                            // Mostrar formulario de búsqueda nuevamente
                            const searchContainer = document.querySelector('#modalFacturacion .mb-4');
                            if (searchContainer) searchContainer.classList.remove('d-none');
                        }
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo eliminar la factura.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
            }
        });
    }
  });

  // ---------------------------------------------------------
  // Lógica para cerrar orden
  // ---------------------------------------------------------
  const btnCerrarOrden = document.getElementById('btn-cerrar-orden');
  if (btnCerrarOrden) {
      if (!userPermissions.canCloseOrder) {
          btnCerrarOrden.disabled = true;
          btnCerrarOrden.title = "No tienes permiso para cerrar órdenes";
      }

      btnCerrarOrden.addEventListener('click', function() {
          Swal.fire({
              title: '¿Cerrar Orden de Trabajo?',
              text: 'Esta acción marcará la orden como cerrada y no se podrán hacer más cambios.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, cerrar orden',
              cancelButtonText: 'Cancelar',
              customClass: {
                  confirmButton: 'btn btn-primary me-3',
                  cancelButton: 'btn btn-label-secondary'
              },
              buttonsStyling: false
          }).then(function(result) {
              if (result.value) {
                  btnCerrarOrden.disabled = true;
                  btnCerrarOrden.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cerrando...';

                  fetch(`${baseUrl}ordenes-trabajo/${ordenId}/cerrar`, {
                      method: 'POST',
                      headers: {
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                          'Content-Type': 'application/json'
                      }
                  })
                  .then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          Swal.fire({
                              icon: 'success',
                              title: '¡Orden Cerrada!',
                              text: 'La orden de trabajo ha sido cerrada exitosamente.',
                              customClass: { confirmButton: 'btn btn-success' }
                          }).then(() => {
                              window.location.reload();
                          });
                      } else {
                          throw new Error(data.error || 'Error al cerrar orden');
                      }
                  })
                  .catch(error => {
                      btnCerrarOrden.disabled = false;
                      btnCerrarOrden.innerHTML = '<i class="icon-base ti tabler-check icon-sm me-1"></i> Cerrar Orden';
                      
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: error.message || 'No se pudo cerrar la orden.',
                          customClass: { confirmButton: 'btn btn-primary' }
                      });
                  });
              }
          });
      });
  }

  // ---------------------------------------------------------
  // Lógica para Archivos Adjuntos
  // ---------------------------------------------------------
  const dropzoneAdjuntosEl = document.getElementById('dropzone-adjuntos');
  const modalSubirAdjuntos = document.getElementById('modalSubirAdjuntos');
  let adjuntosDropzone = null;

  if (dropzoneAdjuntosEl) {
    const previewTemplate = `<div class="dz-preview dz-file-preview">
      <div class="dz-details">
        <div class="dz-thumbnail">
          <img data-dz-thumbnail>
          <span class="dz-nopreview">No preview</span>
          <div class="dz-success-mark"></div>
          <div class="dz-error-mark"></div>
          <div class="dz-error-message"><span data-dz-errormessage></span></div>
          <div class="progress">
            <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
          </div>
        </div>
        <div class="dz-filename" data-dz-name></div>
        <div class="dz-size" data-dz-size></div>
      </div>
    </div>`;

    adjuntosDropzone = new Dropzone(dropzoneAdjuntosEl, {
      previewTemplate: previewTemplate,
      parallelUploads: 1,
      maxFilesize: 20, // 20MB
      addRemoveLinks: true,
      url: `${baseUrl}ordenes-trabajo/${ordenId}/adjuntos`,
      method: 'post',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        Accept: 'application/json'
      },
      autoProcessQueue: true,
      init: function () {
        const dropzone = this;
        dropzone.uploadFiles = realUploadFiles;

        dropzone.on('addedfile', function (file) {
          setTimeout(() => {
            if (dropzone.getQueuedFiles().length > 0) {
              dropzone.processQueue();
            }
          }, 10);
        });

        dropzone.on('success', function (file, response, xhr) {
          let responseData = response;
          if (responseData && responseData.success && responseData.adjunto) {
            agregarAdjuntoALista(responseData.adjunto);
          }
        });
      }
    });
  }

  if (modalSubirAdjuntos && adjuntosDropzone) {
    modalSubirAdjuntos.addEventListener('hidden.bs.modal', function () {
      adjuntosDropzone.removeAllFiles();
    });
  }

  function agregarAdjuntoALista(adjunto) {
    const lista = document.getElementById('lista-adjuntos');
    const noAdjuntosItem = document.getElementById('no-adjuntos');
    if (noAdjuntosItem) noAdjuntosItem.remove();

    const deleteButton = userPermissions.canCreate // Basic check, maybe refine for attachment owner
        ? `<button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill eliminar-adjunto" data-id="${adjunto.id}"><i class="icon-base ti tabler-trash"></i></button>`
        : '';

    const itemHtml = `
      <li class="list-group-item d-flex justify-content-between align-items-center px-0" id="adjunto-${adjunto.id}">
        <div class="d-flex align-items-center">
          <i class="icon-base ti tabler-file me-2"></i>
          <div class="d-flex flex-column">
            <a href="${adjunto.ruta_archivo}" target="_blank" class="text-heading fw-medium">${adjunto.nombre_archivo}</a>
            <small class="text-muted">${adjunto.fecha_formateada}</small>
          </div>
        </div>
        ${deleteButton}
      </li>
    `;
    lista.insertAdjacentHTML('beforeend', itemHtml);
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.eliminar-adjunto')) {
      const btn = e.target.closest('.eliminar-adjunto');
      const id = btn.dataset.id;
      
      Swal.fire({
        title: '¿Eliminar archivo?',
        text: 'No podrás recuperar este archivo',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-danger me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`${baseUrl}ordenes-trabajo/${ordenId}/adjuntos/${id}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Content-Type': 'application/json'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById(`adjunto-${id}`).remove();
              const lista = document.getElementById('lista-adjuntos');
              if (lista.children.length === 0) {
                lista.innerHTML = '<li class="list-group-item px-0 text-center text-muted" id="no-adjuntos">No hay archivos adjuntos</li>';
              }
              Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El archivo ha sido eliminado.',
                customClass: { confirmButton: 'btn btn-success' },
                timer: 1500,
                showConfirmButton: false
              });
            }
          });
        }
      });
    }
  });

  // ---------------------------------------------------------
  // Lógica para Comentarios
  // ---------------------------------------------------------
  const btnEnviarComentario = document.getElementById('btn-enviar-comentario');
  const txtComentario = document.getElementById('nuevo-comentario');

  if (btnEnviarComentario) {
    btnEnviarComentario.addEventListener('click', function () {
      const comentario = txtComentario.value.trim();
      if (!comentario) return;

      btnEnviarComentario.disabled = true;

      fetch(`${baseUrl}ordenes-trabajo/${ordenId}/comentarios`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ comentario: comentario })
      })
      .then(response => response.json())
      .then(data => {
        btnEnviarComentario.disabled = false;
        if (data.success) {
          txtComentario.value = '';
          agregarComentarioALista(data.comentario);
        }
      })
      .catch(error => {
        btnEnviarComentario.disabled = false;
        console.error(error);
      });
    });
  }

  function agregarComentarioALista(comentario) {
    const lista = document.getElementById('lista-comentarios');
    const noComentariosItem = document.getElementById('no-comentarios');
    if (noComentariosItem) noComentariosItem.remove();

    const itemHtml = `
      <li class="chat-message chat-message-right" id="comentario-${comentario.id}">
        <div class="d-flex overflow-hidden">
          <div class="chat-message-wrapper flex-grow-1">
            <div class="chat-message-text">
              <p class="mb-0">${comentario.comentario}</p>
            </div>
            <div class="text-end text-muted mt-1">
              <i class="icon-base ti tabler-checks icon-16px text-success me-1"></i>
              <small>${comentario.usuario_nombre} • ${comentario.fecha_formateada}</small>
              <button type="button" class="btn btn-sm btn-icon btn-text-danger eliminar-comentario ms-1" data-id="${comentario.id}" title="Eliminar">
                <i class="icon-base ti tabler-trash icon-sm"></i>
              </button>
            </div>
          </div>
        </div>
      </li>
    `;
    lista.insertAdjacentHTML('beforeend', itemHtml);
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.eliminar-comentario')) {
      const btn = e.target.closest('.eliminar-comentario');
      const id = btn.dataset.id;

      Swal.fire({
        title: '¿Eliminar comentario?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-danger me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`${baseUrl}ordenes-trabajo/${ordenId}/comentarios/${id}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Content-Type': 'application/json'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById(`comentario-${id}`).remove();
              const lista = document.getElementById('lista-comentarios');
               if (lista.children.length === 0) {
                lista.innerHTML = '<li class="text-center text-muted" id="no-comentarios">No hay comentarios aún</li>';
              }
            }
          });
        }
      });
    }
  });

});
