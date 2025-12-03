/**
 * Dashboard Taller
 */

'use strict';

import Dropzone from 'dropzone';

// Desactivar autodiscover para evitar que Dropzone intente adjuntarse automáticamente
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function () {
    let currentOrdenId = null;
    let myDropzone = null;
    let adjuntosDropzone = null; // Added for offcanvas

    // Inicializar Dropzone
    function initDropzone() {
        const dropzoneEl = document.getElementById('dropzone-dashboard');
        if (!dropzoneEl) return;

        // Destruir instancia previa si existe
        if (myDropzone) {
            myDropzone.destroy();
        }

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

        myDropzone = new Dropzone(dropzoneEl, {
            previewTemplate: previewTemplate,
            parallelUploads: 1,
            maxFilesize: 10, // 10MB
            acceptedFiles: 'image/jpeg,image/jpg,image/png,image/gif,image/webp',
            addRemoveLinks: true,
            url: `${baseUrl}ordenes-trabajo/${currentOrdenId}/fotografias`,
            method: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                Accept: 'application/json'
            },
            autoProcessQueue: true,
            init: function () {
                const dropzone = this;
                
                dropzone.on('addedfile', function (file) {
                  setTimeout(() => {
                    if (dropzone.getQueuedFiles().length > 0) {
                      dropzone.processQueue();
                    }
                  }, 10);
                });

                dropzone.on('success', function (file, response) {
                    if (response && response.success && response.fotografia) {
                        file.fotoId = response.fotografia.id;
                        agregarFotoAGaleria(response.fotografia);
                        updateBotonCompletarFotos();
                    }
                });
                
                dropzone.on('complete', function(file) {
                    // Remove from preview after short delay if successful to keep it clean?
                    // Or keep it. Let's keep it for now.
                });
            }
        });
    }
    
    function agregarFotoAGaleria(foto) {
        const container = document.getElementById('fotografias-container');
        const colVacio = container.querySelector('.col-12 .text-center');
        if (colVacio) colVacio.remove();

        const fotoHtml = `
            <div class="col-6 col-md-4 col-lg-3" data-foto-id="${foto.id}">
                <div class="card h-100">
                    <div class="card-img-wrapper position-relative">
                        <img src="${foto.url_completa || foto.ruta_archivo}" class="card-img-top" alt="${foto.nombre_archivo}" 
                             style="height: 150px; object-fit: cover; cursor: pointer;" 
                             onclick="abrirImagen('${foto.url_completa || foto.ruta_archivo}', '${foto.nombre_archivo}')">
                        <button type="button" class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 m-2 eliminar-foto" 
                                data-foto-id="${foto.id}" title="Eliminar">
                            <i class="icon-base ti tabler-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', fotoHtml);
    }

    function updateBotonCompletarFotos() {
        const btn = document.getElementById('btn-completar-fotos');
        const fotos = document.querySelectorAll('#fotografias-container [data-foto-id]').length;
        if (fotos > 0) {
            btn.disabled = false;
        } else {
            btn.disabled = true;
        }
    }

    // Listeners para los triggers de etapas
    const stageTriggers = document.querySelectorAll('.stage-trigger');
    stageTriggers.forEach(trigger => {
        // Initialize Tooltip for better UX on mobile/stacked view
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
             trigger.setAttribute('data-bs-toggle', 'tooltip');
             trigger.setAttribute('title', trigger.dataset.etapa);
             new bootstrap.Tooltip(trigger);
        }

        trigger.addEventListener('click', function () {
            const etapa = this.dataset.etapa;
            const isActive = this.dataset.isActive === 'true';
            const isCompleted = this.dataset.isCompleted === 'true';
            const card = this.closest('.orden-card');
            currentOrdenId = card.dataset.ordenId;
            const ordenNum = card.querySelector('h6').textContent; // e.g. OT-009

            // Solo permitimos interacción si está activa o completada (para ver histórico)
            // Si es futura (gris), no hacemos nada
            if (!isActive && !isCompleted && etapa !== 'Finalizado') return;

            // Cargar datos de la orden
            loadOrdenData(currentOrdenId).then(data => {
                const orden = data.orden;
                const tecnicos = data.tecnicos;

                // Abrir el modal correspondiente
                switch (etapa) {
                    case 'Toma de fotografías':
                        openModalFotos(orden, ordenNum, isActive);
                        break;
                    case 'Diagnóstico':
                        openModalDiagnostico(orden, ordenNum, isActive, tecnicos);
                        break;
                    case 'Cotizaciones':
                        openModalCotizaciones(orden, ordenNum, isActive);
                        break;
                    case 'Órdenes de Compra':
                        openModalOrdenesCompra(orden, ordenNum, isActive);
                        break;
                    case 'Entrega de repuestos':
                        openModalEntrega(orden, ordenNum, isActive);
                        break;
                    case 'Ejecución':
                        if (isActive) {
                            confirmarEjecucion(currentOrdenId);
                        }
                        break;
                    case 'Facturación':
                        openModalFacturacion(orden, ordenNum, isActive);
                        break;
                    case 'Finalizado':
                        if (isActive) {
                            confirmarCierre(currentOrdenId);
                        }
                        break;
                }
            });
        });
    });

    function loadOrdenData(id) {
        // Mostrar loading overlay global o similar si se desea
        return fetch(`${baseUrl}ordenes-trabajo/${id}/modal-data`)
            .then(res => res.json());
    }

    // ----- Handlers de Modales -----

    function openModalFotos(orden, ordenNum, isActive) {
        const modal = new bootstrap.Modal(document.getElementById('modalFotos'));
        document.getElementById('modalFotosOrden').textContent = ordenNum;
        
        const container = document.getElementById('fotografias-container');
        container.innerHTML = '';
        
        // Dropzone
        const dropzoneContainer = document.getElementById('dropzone-container');
        if (isActive) {
            dropzoneContainer.classList.remove('d-none');
            initDropzone(); // Re-init con nuevo currentOrdenId
        } else {
            dropzoneContainer.classList.add('d-none');
        }

        // Cargar fotos
        if (orden.fotografias && orden.fotografias.length > 0) {
            orden.fotografias.forEach(foto => {
                agregarFotoAGaleria(foto);
            });
        } else {
            container.innerHTML = '<div class="col-12"><div class="text-center py-4 text-muted">No hay fotografías</div></div>';
        }
        
        // Boton completar
        const btn = document.getElementById('btn-completar-fotos');
        if (isActive) {
            btn.classList.remove('d-none');
            updateBotonCompletarFotos();
        } else {
            btn.classList.add('d-none');
        }

        // Disable delete buttons if not active
        if (!isActive) {
            setTimeout(() => {
                container.querySelectorAll('.eliminar-foto').forEach(b => b.remove());
            }, 100);
        }

        modal.show();
    }

    function openModalDiagnostico(orden, ordenNum, isActive, tecnicos) {
        const modal = new bootstrap.Modal(document.getElementById('modalDiagnostico'));
        document.getElementById('modalDiagnosticoOrden').textContent = ordenNum;
        
        const form = document.getElementById('form-diagnostico');
        form.reset();
        
        // Llenar select tecnicos
        const selectTecnico = $('#diagnosticado_por');
        selectTecnico.empty();
        selectTecnico.append('<option value="">Seleccionar técnico</option>');
        tecnicos.forEach(t => {
            selectTecnico.append(new Option(t.name, t.id));
        });

        // Llenar datos
        $('#duracion_diagnostico').val(orden.duracion_diagnostico);
        selectTecnico.val(orden.diagnosticado_por).trigger('change');
        $('#detalle_diagnostico').val(orden.detalle_diagnostico);

        // Habilitar/Deshabilitar
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(i => i.disabled = !isActive);
        selectTecnico.prop('disabled', !isActive);

        const btnGuardar = document.getElementById('btn-guardar-diagnostico');
        const btnCompletar = document.getElementById('btn-completar-diagnostico');

        if (isActive) {
            btnGuardar.classList.remove('d-none');
            btnCompletar.classList.remove('d-none');
            btnCompletar.disabled = !orden.duracion_diagnostico;
        } else {
            btnGuardar.classList.add('d-none');
            btnCompletar.classList.add('d-none');
        }

        modal.show();
    }

    function openModalCotizaciones(orden, ordenNum, isActive) {
        const modal = new bootstrap.Modal(document.getElementById('modalCotizaciones'));
        document.getElementById('modalCotizacionesOrden').textContent = ordenNum;

        const searchContainer = document.getElementById('busqueda-cotizacion-container');
        if (isActive) {
            searchContainer.classList.remove('d-none');
        } else {
            searchContainer.classList.add('d-none');
        }
        
        // Limpiar busqueda previa
        document.getElementById('numero_cotizacion_alegra').value = '';
        document.getElementById('resultado-cotizacion').classList.add('d-none');
        window.cotizacionEncontrada = null;

        // Llenar tabla
        const tbody = document.getElementById('tabla-cotizaciones');
        tbody.innerHTML = '';
        
        if (orden.cotizaciones && orden.cotizaciones.length > 0) {
            orden.cotizaciones.forEach(cot => {
                // Create row
                const tr = document.createElement('tr');
                tr.dataset.id = cot.id;
                
                let actionsHtml = '';
                if (isActive) {
                    if (!cot.aprobada) {
                        actionsHtml += `<button type="button" class="btn btn-sm btn-icon btn-success aprobar-cotizacion me-1" data-id="${cot.id}"><i class="icon-base ti tabler-check"></i></button>`;
                    }
                    actionsHtml += `<button type="button" class="btn btn-sm btn-icon btn-danger eliminar-cotizacion" data-id="${cot.id}"><i class="icon-base ti tabler-trash"></i></button>`;
                }

                tr.innerHTML = `
                    <td>${cot.numero_cotizacion}</td>
                    <td>${cot.cliente_nombre}</td>
                    <td>${cot.fecha_emision}</td>
                    <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(cot.total)}</td>
                    <td>${cot.url_pdf ? `<a href="${cot.url_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary"><i class="icon-base ti tabler-file-type-pdf"></i></a>` : '-'}</td>
                    <td>${cot.aprobada ? '<span class="badge bg-label-success">Aprobada</span>' : '<span class="badge bg-label-secondary">Sin aprobar</span>'}</td>
                    <td>${actionsHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr id="no-cotizaciones"><td colspan="7" class="text-center text-muted">No hay cotizaciones</td></tr>';
        }

        const btnCompletar = document.getElementById('btn-completar-cotizaciones');
        if (isActive) {
            btnCompletar.classList.remove('d-none');
            const hasApproved = orden.cotizaciones.some(c => c.aprobada);
            btnCompletar.disabled = !hasApproved;
        } else {
            btnCompletar.classList.add('d-none');
        }

        modal.show();
    }
    
    function openModalOrdenesCompra(orden, ordenNum, isActive) {
        const modal = new bootstrap.Modal(document.getElementById('modalOrdenesCompra'));
        document.getElementById('modalOrdenesCompraOrden').textContent = ordenNum;

        const searchContainer = document.getElementById('busqueda-oc-container');
        if (isActive) {
            searchContainer.classList.remove('d-none');
        } else {
            searchContainer.classList.add('d-none');
        }
        
        // Limpiar
        document.getElementById('numero_orden_compra_alegra').value = '';
        document.getElementById('resultado-orden-compra').classList.add('d-none');
        window.ordenCompraEncontrada = null;

        // Tabla
        const tbody = document.getElementById('tabla-ordenes-compra');
        tbody.innerHTML = '';
        
        if (orden.ordenes_compra && orden.ordenes_compra.length > 0) {
            orden.ordenes_compra.forEach(oc => {
                const tr = document.createElement('tr');
                tr.dataset.id = oc.id;
                let actionsHtml = isActive ? `<button type="button" class="btn btn-sm btn-icon btn-danger eliminar-orden-compra" data-id="${oc.id}"><i class="icon-base ti tabler-trash"></i></button>` : '';

                tr.innerHTML = `
                    <td>${oc.numero_orden}</td>
                    <td>${oc.proveedor_nombre}</td>
                    <td>${oc.fecha_emision}</td>
                    <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(oc.total)}</td>
                    <td>${oc.url_pdf ? `<a href="${oc.url_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary"><i class="icon-base ti tabler-file-type-pdf"></i></a>` : '-'}</td>
                    <td>${actionsHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr id="no-ordenes-compra"><td colspan="6" class="text-center text-muted">No hay órdenes de compra</td></tr>';
        }

        const btnCompletar = document.getElementById('btn-completar-ordenes-compra');
        if (isActive) {
            btnCompletar.classList.remove('d-none');
            btnCompletar.disabled = !(orden.ordenes_compra && orden.ordenes_compra.length > 0);
        } else {
            btnCompletar.classList.add('d-none');
        }

        modal.show();
    }

    function openModalEntrega(orden, ordenNum, isActive) {
        const modal = new bootstrap.Modal(document.getElementById('modalEntregaRepuestos'));
        document.getElementById('modalEntregaRepuestosOrden').textContent = ordenNum;

        const checkRepuestos = document.getElementById('check-repuestos-entregados');
        const checkTiquete = document.getElementById('check-tiquete-impreso');
        const btnImprimir = document.getElementById('btn-imprimir-tiquete');
        const btnCompletar = document.getElementById('btn-completar-entrega-repuestos');

        checkRepuestos.checked = orden.repuestos_entregados;
        checkTiquete.checked = orden.tiquete_impreso;
        
        checkRepuestos.disabled = !isActive;
        // Tiquete check is always disabled, updated by print action

        // Set href for printing
        if(window.routeTiqueteBase) {
            // replace base url placeholder if any or construct url
            // Assuming window.routeTiqueteBase is http://.../ordenes-trabajo
            // The route is /ordenes-trabajo/{id}/imprimir-tiquete-repuestos
             btnImprimir.href = `${baseUrl}ordenes-trabajo/${orden.id}/imprimir-tiquete-repuestos`;
        }

        if (isActive) {
            btnCompletar.classList.remove('d-none');
            btnCompletar.disabled = !(orden.repuestos_entregados && orden.tiquete_impreso);
        } else {
            btnCompletar.classList.add('d-none');
        }

        modal.show();
    }

    function openModalFacturacion(orden, ordenNum, isActive) {
        const modal = new bootstrap.Modal(document.getElementById('modalFacturacion'));
        document.getElementById('modalFacturacionOrden').textContent = ordenNum;

        const searchContainer = document.getElementById('busqueda-factura-container');
        if (isActive) {
             // Mostrar busqueda solo si no hay facturas o se permite multiple (aqui solo 1)
             if (orden.facturas && orden.facturas.length > 0) {
                 searchContainer.classList.add('d-none');
             } else {
                 searchContainer.classList.remove('d-none');
             }
        } else {
            searchContainer.classList.add('d-none');
        }
        
        // Limpiar
        document.getElementById('numero_factura_alegra').value = '';
        document.getElementById('resultado-factura').classList.add('d-none');
        window.facturaEncontrada = null;

        // Tabla
        const tbody = document.getElementById('tabla-facturas');
        tbody.innerHTML = '';
        
        if (orden.facturas && orden.facturas.length > 0) {
            orden.facturas.forEach(fac => {
                const tr = document.createElement('tr');
                tr.dataset.id = fac.id;
                let actionsHtml = isActive ? `<button type="button" class="btn btn-sm btn-icon btn-danger eliminar-factura" data-id="${fac.id}"><i class="icon-base ti tabler-trash"></i></button>` : '';

                tr.innerHTML = `
                    <td>${fac.numero_factura}</td>
                    <td>${fac.cliente_nombre}</td>
                    <td>${fac.fecha_emision}</td>
                    <td>${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(fac.total)}</td>
                    <td>${fac.url_pdf ? `<a href="${fac.url_pdf}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary"><i class="icon-base ti tabler-file-type-pdf"></i></a>` : '-'}</td>
                    <td>${actionsHtml}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr id="no-facturas"><td colspan="6" class="text-center text-muted">No hay facturas</td></tr>';
        }

        const btnCompletar = document.getElementById('btn-completar-facturacion');
        if (isActive) {
            btnCompletar.classList.remove('d-none');
            btnCompletar.disabled = !(orden.facturas && orden.facturas.length > 0);
        } else {
            btnCompletar.classList.add('d-none');
        }

        modal.show();
    }

    function confirmarEjecucion(ordenId) {
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
        }).then((result) => {
            if (result.isConfirmed) {
                avanzarEtapa(ordenId);
            }
        });
    }

    function confirmarCierre(ordenId) {
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
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${baseUrl}ordenes-trabajo/${ordenId}/cerrar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                         Swal.fire('¡Cerrada!', 'La orden ha sido cerrada.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.error || 'Error al cerrar', 'error');
                    }
                });
            }
        });
    }

    // ----- Acciones Comunes (AJAX) -----
    
    function avanzarEtapa(ordenId) {
        fetch(`${baseUrl}ordenes-trabajo/${ordenId}/avanzar-etapa`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('¡Éxito!', 'Etapa completada.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.error, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Error de red', 'error'));
    }

    // Listeners para botones dentro de modales
    
    // Fotos: Eliminar
    document.body.addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-foto')) {
            const btn = e.target.closest('.eliminar-foto');
            const fotoId = btn.dataset.fotoId;
            
            Swal.fire({
                title: '¿Eliminar foto?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/fotografias/${fotoId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    }).then(res => res.json()).then(data => {
                         if(data.success) {
                             btn.closest('.col-6').remove();
                             updateBotonCompletarFotos();
                         }
                    });
                }
            });
        }
    });

    // Fotos: Completar
    const btnCompletarFotos = document.getElementById('btn-completar-fotos');
    if(btnCompletarFotos) {
        btnCompletarFotos.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }

    // Diagnostico: Guardar
    const btnGuardarDiag = document.getElementById('btn-guardar-diagnostico');
    if(btnGuardarDiag) {
        btnGuardarDiag.addEventListener('click', function() {
            const form = document.getElementById('form-diagnostico');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            // Select2 fix
            data.diagnosticado_por = $('#diagnosticado_por').val();

             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/diagnostico`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Guardado', data.message, 'success');
                    document.getElementById('btn-completar-diagnostico').disabled = false;
                } else {
                     Swal.fire('Error', 'Revisa los campos', 'error');
                }
            });
        });
    }
    
    // Diagnostico: Completar
    const btnCompletarDiag = document.getElementById('btn-completar-diagnostico');
    if(btnCompletarDiag) {
        btnCompletarDiag.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }
    
    // Cotizaciones: Buscar
    const btnBuscarCot = document.getElementById('btn-buscar-cotizacion');
    if (btnBuscarCot) {
        btnBuscarCot.addEventListener('click', function() {
             const num = document.getElementById('numero_cotizacion_alegra').value;
             fetch(`${baseUrl}ordenes-trabajo/buscar-cotizacion-alegra`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify({numero_cotizacion: num})
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     window.cotizacionEncontrada = data.data;
                     document.getElementById('cotizacion-info').innerHTML = `Total: ${data.data.total} - Cliente: ${data.data.cliente}`;
                     document.getElementById('resultado-cotizacion').classList.remove('d-none');
                 } else {
                     Swal.fire('No encontrada', data.message, 'warning');
                 }
             });
        });
    }
    
    // Cotizaciones: Agregar
    const btnAgregarCot = document.getElementById('btn-agregar-cotizacion');
    if (btnAgregarCot) {
        btnAgregarCot.addEventListener('click', function() {
             if(!window.cotizacionEncontrada) return;
             const payload = {
                 alegra_id: window.cotizacionEncontrada.id,
                 numero_cotizacion: window.cotizacionEncontrada.numero,
                 cliente_nombre: window.cotizacionEncontrada.cliente,
                 fecha_emision: window.cotizacionEncontrada.fecha,
                 total: window.cotizacionEncontrada.total
             };
             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/cotizaciones`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify(payload)
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     // Reload current modal state by fetching data again is safer but slower
                     // For now just hide result and clear
                     document.getElementById('resultado-cotizacion').classList.add('d-none');
                     document.getElementById('numero_cotizacion_alegra').value = '';
                     // Reload modal
                     loadOrdenData(currentOrdenId).then(d => openModalCotizaciones(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
                 }
             });
        });
    }
    
    // Cotizaciones: Aprobar y Eliminar (Delegate)
    document.getElementById('tabla-cotizaciones').addEventListener('click', function(e) {
         if(e.target.closest('.eliminar-cotizacion')) {
             const id = e.target.closest('.eliminar-cotizacion').dataset.id;
             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/cotizaciones/${id}`, {
                 method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
             }).then(() => {
                 e.target.closest('tr').remove();
                 // Check button state logic... re-fetch easiest
                  loadOrdenData(currentOrdenId).then(d => openModalCotizaciones(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
             });
         }
         if(e.target.closest('.aprobar-cotizacion')) {
             const id = e.target.closest('.aprobar-cotizacion').dataset.id;
             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/cotizaciones/${id}/aprobar`, {
                 method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
             }).then(() => {
                  loadOrdenData(currentOrdenId).then(d => openModalCotizaciones(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
             });
         }
    });

    // Cotizaciones: Completar
    const btnCompletarCot = document.getElementById('btn-completar-cotizaciones');
    if(btnCompletarCot) {
        btnCompletarCot.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }
    
    // Reuse logic for Orders and Invoices similar to Quotes...
    // Ordenes Compra: Buscar
    const btnBuscarOC = document.getElementById('btn-buscar-orden-compra');
    if (btnBuscarOC) {
        btnBuscarOC.addEventListener('click', function() {
             const num = document.getElementById('numero_orden_compra_alegra').value;
             fetch(`${baseUrl}ordenes-trabajo/buscar-orden-compra-alegra`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify({numero_orden: num})
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     window.ordenCompraEncontrada = data.data;
                     document.getElementById('orden-compra-info').innerHTML = `Total: ${data.data.total} - Prov: ${data.data.proveedor}`;
                     document.getElementById('resultado-orden-compra').classList.remove('d-none');
                 } else {
                     Swal.fire('No encontrada', data.message, 'warning');
                 }
             });
        });
    }
    // Ordenes Compra: Agregar
    const btnAgregarOC = document.getElementById('btn-agregar-orden-compra');
    if (btnAgregarOC) {
        btnAgregarOC.addEventListener('click', function() {
             if(!window.ordenCompraEncontrada) return;
             const payload = {
                 alegra_id: window.ordenCompraEncontrada.id,
                 numero_orden: window.ordenCompraEncontrada.numero,
                 proveedor_nombre: window.ordenCompraEncontrada.proveedor,
                 fecha_emision: window.ordenCompraEncontrada.fecha,
                 total: window.ordenCompraEncontrada.total
             };
             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/ordenes-compra`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify(payload)
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     loadOrdenData(currentOrdenId).then(d => openModalOrdenesCompra(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
                 }
             });
        });
    }
    // Ordenes Compra: Eliminar
    document.getElementById('tabla-ordenes-compra').addEventListener('click', function(e) {
        if(e.target.closest('.eliminar-orden-compra')) {
            const id = e.target.closest('.eliminar-orden-compra').dataset.id;
            fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/ordenes-compra/${id}`, {
                method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
            }).then(() => {
                loadOrdenData(currentOrdenId).then(d => openModalOrdenesCompra(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
            });
        }
    });
    // Ordenes Compra: Completar
    const btnCompletarOC = document.getElementById('btn-completar-ordenes-compra');
    if(btnCompletarOC) {
        btnCompletarOC.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }
    
    // Repuestos
    const checkRepuestos = document.getElementById('check-repuestos-entregados');
    if(checkRepuestos) {
        checkRepuestos.addEventListener('change', function() {
            fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/entrega-repuestos`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                body: JSON.stringify({
                    repuestos_entregados: this.checked,
                    tiquete_impreso: document.getElementById('check-tiquete-impreso').checked
                })
            }).then(res => res.json()).then(data => {
                 if(data.success) {
                     // check if can complete
                     if(this.checked && document.getElementById('check-tiquete-impreso').checked) {
                         document.getElementById('btn-completar-entrega-repuestos').disabled = false;
                     } else {
                         document.getElementById('btn-completar-entrega-repuestos').disabled = true;
                     }
                 }
            });
        });
    }
    const btnImprimir = document.getElementById('btn-imprimir-tiquete');
    if(btnImprimir) {
        btnImprimir.addEventListener('click', function() {
             // Assume printed
             setTimeout(() => {
                 document.getElementById('check-tiquete-impreso').checked = true;
                 // trigger update
                 fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/entrega-repuestos`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        repuestos_entregados: document.getElementById('check-repuestos-entregados').checked,
                        tiquete_impreso: true
                    })
                }).then(() => {
                    if(document.getElementById('check-repuestos-entregados').checked) {
                        document.getElementById('btn-completar-entrega-repuestos').disabled = false;
                    }
                });
             }, 1000);
        });
    }
    const btnCompletarRep = document.getElementById('btn-completar-entrega-repuestos');
    if(btnCompletarRep) {
        btnCompletarRep.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }
    
    // Facturacion: Buscar
    const btnBuscarFac = document.getElementById('btn-buscar-factura');
    if (btnBuscarFac) {
        btnBuscarFac.addEventListener('click', function() {
             const num = document.getElementById('numero_factura_alegra').value;
             fetch(`${baseUrl}ordenes-trabajo/buscar-factura-alegra`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify({numero_factura: num})
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     window.facturaEncontrada = data.data;
                     document.getElementById('factura-info').innerHTML = `Total: ${data.data.total} - Cliente: ${data.data.cliente}`;
                     document.getElementById('resultado-factura').classList.remove('d-none');
                 } else {
                     Swal.fire('No encontrada', data.message, 'warning');
                 }
             });
        });
    }
    // Facturacion: Agregar
    const btnAgregarFac = document.getElementById('btn-agregar-factura');
    if (btnAgregarFac) {
        btnAgregarFac.addEventListener('click', function() {
             if(!window.facturaEncontrada) return;
             const payload = {
                 alegra_id: window.facturaEncontrada.id,
                 numero_factura: window.facturaEncontrada.numero,
                 cliente_nombre: window.facturaEncontrada.cliente,
                 fecha_emision: window.facturaEncontrada.fecha,
                 total: window.facturaEncontrada.total
             };
             fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/facturas`, {
                 method: 'POST',
                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'},
                 body: JSON.stringify(payload)
             }).then(res => res.json()).then(data => {
                 if(data.success) {
                     loadOrdenData(currentOrdenId).then(d => openModalFacturacion(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
                 } else {
                     Swal.fire('Error', data.message, 'error');
                 }
             });
        });
    }
    // Facturacion: Eliminar
    document.getElementById('tabla-facturas').addEventListener('click', function(e) {
        if(e.target.closest('.eliminar-factura')) {
            const id = e.target.closest('.eliminar-factura').dataset.id;
            fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/facturas/${id}`, {
                method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
            }).then(() => {
                loadOrdenData(currentOrdenId).then(d => openModalFacturacion(d.orden, `OT-${String(d.orden.id).padStart(3,'0')}`, true));
            });
        }
    });
    // Facturacion: Completar
    const btnCompletarFac = document.getElementById('btn-completar-facturacion');
    if(btnCompletarFac) {
        btnCompletarFac.addEventListener('click', () => avanzarEtapa(currentOrdenId));
    }

    // ---------------------------------------------------------
    // OFFCANVAS LOGIC (Adjuntos & Comentarios)
    // ---------------------------------------------------------

    // ADJUNTOS
    const offcanvasAdjuntos = document.getElementById('offcanvasAdjuntos');
    if (offcanvasAdjuntos) {
        // Trigger open
        document.body.addEventListener('click', function(e) {
            const trigger = e.target.closest('.trigger-adjuntos');
            if (trigger) {
                e.preventDefault();
                const id = trigger.dataset.id;
                currentOrdenId = id;
                openOffcanvasAdjuntos(id);
            }
        });

        // Subir archivo (Toggle Dropzone)
        const btnSubir = document.getElementById('btn-subir-adjunto-offcanvas');
        const dzContainer = document.getElementById('dropzone-adjuntos-offcanvas-container');
        if (btnSubir && dzContainer) {
            btnSubir.addEventListener('click', function() {
                dzContainer.classList.toggle('d-none');
                if (!dzContainer.classList.contains('d-none')) {
                    initAdjuntosDropzone();
                }
            });
        }
    }

    function openOffcanvasAdjuntos(id) {
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasAdjuntos'));
        const list = document.getElementById('lista-adjuntos-offcanvas');
        list.innerHTML = '<li class="list-group-item text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></li>';
        
        // Reset dropzone visibility
        document.getElementById('dropzone-adjuntos-offcanvas-container').classList.add('d-none');

        loadOrdenData(id).then(data => {
            renderAdjuntosList(data.orden.adjuntos);
        });
        
        offcanvas.show();
    }

    function renderAdjuntosList(adjuntos) {
        const list = document.getElementById('lista-adjuntos-offcanvas');
        list.innerHTML = '';

        if (adjuntos && adjuntos.length > 0) {
            adjuntos.forEach(adj => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center px-0';
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="ti tabler-file me-2"></i>
                        <div class="d-flex flex-column">
                            <a href="${adj.url_completa || adj.ruta_archivo}" target="_blank" class="text-heading fw-medium text-truncate" style="max-width: 200px;">${adj.nombre_archivo}</a>
                            <small class="text-muted">${adj.fecha_formateada}</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill eliminar-adjunto-offcanvas" data-id="${adj.id}">
                        <i class="ti tabler-trash"></i>
                    </button>
                `;
                list.appendChild(li);
            });
        } else {
            list.innerHTML = '<li class="list-group-item text-center text-muted">No hay archivos adjuntos</li>';
        }
    }

    function initAdjuntosDropzone() {
        const dzEl = document.getElementById('dropzone-adjuntos-offcanvas');
        if (!dzEl) return;
        
        if (adjuntosDropzone) adjuntosDropzone.destroy();

        adjuntosDropzone = new Dropzone(dzEl, {
            url: `${baseUrl}ordenes-trabajo/${currentOrdenId}/adjuntos`,
            method: 'post',
            maxFilesize: 20,
            parallelUploads: 1,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                Accept: 'application/json'
            },
            init: function() {
                this.on("success", function(file, response) {
                    if (response.success) {
                        // Refresh list
                        loadOrdenData(currentOrdenId).then(data => renderAdjuntosList(data.orden.adjuntos));
                        // Remove file from view after a bit
                        setTimeout(() => this.removeFile(file), 1000);
                    }
                });
            }
        });
    }

    // Eliminar adjunto offcanvas
    document.getElementById('lista-adjuntos-offcanvas').addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-adjunto-offcanvas')) {
            const btn = e.target.closest('.eliminar-adjunto-offcanvas');
            const id = btn.dataset.id;
            
            Swal.fire({
                title: '¿Eliminar?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí'
            }).then((r) => {
                if (r.isConfirmed) {
                    fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/adjuntos/${id}`, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            loadOrdenData(currentOrdenId).then(d => renderAdjuntosList(d.orden.adjuntos));
                        }
                    });
                }
            });
        }
    });


    // COMENTARIOS
    const offcanvasComentarios = document.getElementById('offcanvasComentarios');
    if (offcanvasComentarios) {
        // Trigger open
        document.body.addEventListener('click', function(e) {
            const trigger = e.target.closest('.trigger-comentarios');
            if (trigger) {
                e.preventDefault();
                const id = trigger.dataset.id;
                currentOrdenId = id;
                openOffcanvasComentarios(id);
            }
        });

        // Enviar comentario
        const btnEnviar = document.getElementById('btn-enviar-comentario-offcanvas');
        if (btnEnviar) {
            btnEnviar.addEventListener('click', function() {
                const txt = document.getElementById('nuevo-comentario-offcanvas');
                const comentario = txt.value.trim();
                if (!comentario) return;

                btnEnviar.disabled = true;
                fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/comentarios`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ comentario: comentario })
                }).then(res => res.json()).then(data => {
                    btnEnviar.disabled = false;
                    if (data.success) {
                        txt.value = '';
                        loadOrdenData(currentOrdenId).then(d => renderComentariosList(d.orden.comentarios));
                    }
                });
            });
        }
    }

    function openOffcanvasComentarios(id) {
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasComentarios'));
        const list = document.getElementById('lista-comentarios-offcanvas');
        list.innerHTML = '<div class="text-center mt-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';
        
        loadOrdenData(id).then(data => {
            renderComentariosList(data.orden.comentarios);
        });
        
        offcanvas.show();
    }

    function renderComentariosList(comentarios) {
        const list = document.getElementById('lista-comentarios-offcanvas');
        list.innerHTML = '';

        if (comentarios && comentarios.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-unstyled chat-history mb-0';
            
            comentarios.forEach(com => {
                const isMe = com.user_id === window.currentUserId;
                const li = document.createElement('li');
                li.className = `chat-message ${isMe ? 'chat-message-right' : ''}`;
                li.id = `comentario-${com.id}`;
                li.style.marginBlockEnd = '1rem';

                li.innerHTML = `
                    <div class="d-flex overflow-hidden">
                      <div class="chat-message-wrapper flex-grow-1">
                        <div class="chat-message-text">
                          <p class="mb-0">${com.comentario}</p>
                        </div>
                        <div class="${isMe ? 'text-end text-muted' : 'text-muted'} mt-1">
                          ${isMe ? '<i class="icon-base ti tabler-checks icon-16px text-success me-1"></i>' : ''}
                          <small>${com.usuario ? com.usuario.name : 'Usuario'} • ${com.fecha_formateada}</small>
                          ${isMe ? `<button type="button" class="btn btn-sm btn-icon btn-text-danger eliminar-comentario-offcanvas ms-1" data-id="${com.id}" title="Eliminar"><i class="icon-base ti tabler-trash icon-sm"></i></button>` : ''}
                        </div>
                      </div>
                    </div>
                `;
                ul.appendChild(li);
            });
            list.appendChild(ul);
            
            // Auto scroll to bottom
            setTimeout(() => {
                list.scrollTop = list.scrollHeight;
            }, 100);
        } else {
            list.innerHTML = '<div class="text-center text-muted mt-4">No hay comentarios aún</div>';
        }
    }

    // Eliminar comentario offcanvas
    document.getElementById('lista-comentarios-offcanvas').addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-comentario-offcanvas')) {
            const btn = e.target.closest('.eliminar-comentario-offcanvas');
            const id = btn.dataset.id;
            
            Swal.fire({
                title: '¿Eliminar?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí'
            }).then((r) => {
                if (r.isConfirmed) {
                    fetch(`${baseUrl}ordenes-trabajo/${currentOrdenId}/comentarios/${id}`, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            loadOrdenData(currentOrdenId).then(d => renderComentariosList(d.orden.comentarios));
                        } else {
                            Swal.fire('Error', data.error || 'No permitido', 'error');
                        }
                    });
                }
            });
        }
    });

});
