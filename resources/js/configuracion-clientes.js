/**
 * Page Clientes management
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_cliente_table = document.querySelector('.datatables-clientes'),
    offCanvasForm = document.getElementById('offcanvasAddCliente');

  // Declare dt_cliente in outer scope
  let dt_cliente;

  // Select2 initialization
  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar opción',
      dropdownParent: $this.parent()
    });
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Buscar en Alegra
  const btnBuscarAlegra = document.getElementById('btn-buscar-alegra');
  if (btnBuscarAlegra) {
    btnBuscarAlegra.addEventListener('click', function () {
      const numeroIdentificacion = document.getElementById('add-cliente-numero-identificacion').value.trim();

      if (!numeroIdentificacion) {
        Swal.fire({
          icon: 'warning',
          title: 'Campo requerido',
          text: 'Por favor ingrese el número de identificación para buscar.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
        return;
      }

      // Deshabilitar botón mientras se busca
      btnBuscarAlegra.disabled = true;
      btnBuscarAlegra.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

      fetch(`${baseUrl}clientes/buscar-alegra?numero_identificacion=${encodeURIComponent(numeroIdentificacion)}`, {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          Accept: 'application/json'
        }
      })
        .then(response => {
          if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
          }
          return response.json();
        })
        .then(data => {
          if (data.success && data.data) {
            // Autocompletar campos del formulario
            if (data.data.nombre) {
              document.getElementById('add-cliente-nombre').value = data.data.nombre;
            }
            if (data.data.correo_electronico) {
              document.getElementById('add-cliente-correo').value = data.data.correo_electronico;
            }
            if (data.data.telefono) {
              document.getElementById('add-cliente-telefono').value = data.data.telefono;
            }
            if (data.data.direccion) {
              document.getElementById('add-cliente-direccion').value = data.data.direccion;
            }
            // El número de identificación ya está en el campo, no lo sobrescribimos

            Swal.fire({
              icon: 'success',
              title: '¡Encontrado!',
              text: 'Los datos se han cargado desde Alegra. Por favor seleccione el tipo de identificación y revise la información antes de guardar.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          } else {
            Swal.fire({
              icon: 'info',
              title: 'No encontrado',
              text: 'No se encontró ningún contacto con ese número de identificación en Alegra.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        })
        .catch(error => {
          let errorMessage = 'No se pudo conectar con Alegra.';
          if (error.message) {
            errorMessage = error.message;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        })
        .finally(() => {
          // Rehabilitar botón
          btnBuscarAlegra.disabled = false;
          btnBuscarAlegra.innerHTML = '<i class="icon-base ti tabler-search icon-sm"></i>';
        });
    });
  }

  // Clientes datatable
  if (dt_cliente_table) {
    dt_cliente = new DataTable(dt_cliente_table, {
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'clientes/list',
        dataSrc: function (json) {
          if (typeof json.recordsTotal !== 'number') {
            json.recordsTotal = 0;
          }
          if (typeof json.recordsFiltered !== 'number') {
            json.recordsFiltered = 0;
          }
          json.data = Array.isArray(json.data) ? json.data : [];
          return json.data;
        }
      },
      columns: [
        { data: 'id' },
        { data: 'id' },
        { data: 'tipo_identificacion' },
        { data: 'numero_identificacion' },
        { data: 'nombre' },
        { data: 'correo_electronico' },
        { data: 'telefono' },
        { data: 'direccion' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          searchable: false,
          orderable: false,
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const tipo = full['tipo_identificacion'] || '';
            return `<span class="fw-medium">${tipo}</span>`;
          }
        },
        {
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const numero = full['numero_identificacion'] || '';
            return `<span class="fw-medium">${numero}</span>`;
          }
        },
        {
          targets: 4,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const nombre = full['nombre'] || '';
            return `<span class="fw-medium">${nombre}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            const correo = full['correo_electronico'] || '';
            return `<span>${correo || '-'}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            const telefono = full['telefono'] || '';
            return `<span>${telefono || '-'}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            const direccion = full['direccion'] || '';
            const truncated = direccion.length > 50 ? direccion.substring(0, 50) + '...' : direccion;
            return `<span title="${direccion}">${truncated || '-'}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-4">' +
              `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddCliente"><i class="icon-base ti tabler-edit icon-22px"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record" data-id="${full['id']}"><i class="icon-base ti tabler-trash icon-22px"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[4, 'asc']],
      layout: {
        topStart: {
          rowClass: 'row m-3 my-0 justify-content-between',
          features: [
            {
              pageLength: {
                menu: [7, 10, 20, 50, 70, 100],
                text: '_MENU_'
              }
            }
          ]
        },
        topEnd: {
          features: [
            {
              search: {
                placeholder: 'Buscar cliente',
                text: '_INPUT_'
              }
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: [
            {
              info: {
                text: 'Mostrando _START_ a _END_ de _TOTAL_ registros'
              }
            }
          ]
        },
        bottomEnd: 'paging'
      },
      displayLength: 7,
      language: {
        paginate: {
          first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
          last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>',
          next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
          previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>'
        }
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de ' + data['nombre'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.title !== ''
                  ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                    </tr>`
                  : '';
              })
              .join('');

            if (data) {
              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              div.appendChild(table);
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              return div;
            }
            return false;
          }
        }
      }
    });

    // Delete Record
    document.addEventListener('click', function (e) {
      if (e.target.closest('.delete-record')) {
        const deleteBtn = e.target.closest('.delete-record');
        const cliente_id = deleteBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        Swal.fire({
          title: '¿Estás seguro?',
          text: '¡No podrás revertir esto!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar!',
          cancelButtonText: 'Cancelar',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.value) {
            fetch(`${baseUrl}clientes/${cliente_id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
              }
            })
              .then(response => {
                if (response.ok) {
                  if (dt_cliente) {
                    dt_cliente.draw();
                  }
                  Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El cliente ha sido eliminado.',
                    customClass: {
                      confirmButton: 'btn btn-success'
                    }
                  });
                } else {
                  throw new Error('Delete failed');
                }
              })
              .catch(error => {
                console.log(error);
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'No se pudo eliminar el cliente.',
                  customClass: {
                    confirmButton: 'btn btn-success'
                  }
                });
              });
          }
        });
      }
    });

    // edit record
    document.addEventListener('click', function (e) {
      if (e.target.closest('.edit-record')) {
        const editBtn = e.target.closest('.edit-record');
        const cliente_id = editBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        document.getElementById('offcanvasAddClienteLabel').innerHTML = 'Editar Cliente';

        fetch(`${baseUrl}clientes/${cliente_id}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('cliente_id').value = data.id;
            $('#add-cliente-tipo-identificacion').val(data.tipo_identificacion).trigger('change');
            document.getElementById('add-cliente-numero-identificacion').value = data.numero_identificacion;
            document.getElementById('add-cliente-nombre').value = data.nombre;
            document.getElementById('add-cliente-correo').value = data.correo_electronico || '';
            document.getElementById('add-cliente-telefono').value = data.telefono || '';
            document.getElementById('add-cliente-direccion').value = data.direccion || '';
          });
      }
    });

    // changing the title
    const addNewBtn = document.querySelector('.add-new');
    if (addNewBtn) {
      addNewBtn.addEventListener('click', function () {
        document.getElementById('cliente_id').value = '';
        $('#add-cliente-tipo-identificacion').val('').trigger('change');
        document.getElementById('add-cliente-numero-identificacion').value = '';
        document.getElementById('add-cliente-nombre').value = '';
        document.getElementById('add-cliente-correo').value = '';
        document.getElementById('add-cliente-telefono').value = '';
        document.getElementById('add-cliente-direccion').value = '';
        document.getElementById('offcanvasAddClienteLabel').innerHTML = 'Agregar Cliente';
      });
    }
  }

  // validating form and updating cliente's data
  const addNewClienteForm = document.getElementById('addNewClienteForm');

  if (addNewClienteForm) {
    const fv = FormValidation.formValidation(addNewClienteForm, {
      fields: {
        tipo_identificacion: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione el tipo de identificación'
            }
          }
        },
        numero_identificacion: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el número de identificación'
            }
          }
        },
        nombre: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el nombre del cliente'
            }
          }
        },
        correo_electronico: {
          validators: {
            emailAddress: {
              message: 'Por favor ingrese un correo electrónico válido'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: function (field, ele) {
            return '.mb-6';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      },
      init: instance => {
        instance.on('plugins.message.placed', function (e) {
          //* Move the error message out of the `input-group` element
          if (e.element.parentElement.classList.contains('input-group')) {
            // `e.field`: The field name
            // `e.messageElement`: The message element
            // `e.element`: The field element
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
          //* Move the error message out of the `row` element for custom-options
          if (e.element.parentElement.parentElement.classList.contains('custom-option')) {
            e.element.closest('.row').insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    }).on('core.form.valid', function () {
      const formData = new FormData(addNewClienteForm);
      const formDataObj = {};

      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });

      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(formDataObj)) {
        searchParams.append(key, value);
      }

      fetch(`${baseUrl}clientes`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json'
        },
        body: searchParams.toString()
      })
        .then(async response => {
          const contentType = response.headers.get('content-type');
          const isJson = contentType && contentType.includes('application/json');

          if (!response.ok) {
            // Si la respuesta es JSON, parsearla como error
            if (isJson) {
              const errorData = await response.json();
              return Promise.reject(errorData);
            } else {
              // Si no es JSON, crear un error genérico
              const text = await response.text();
              return Promise.reject({ message: text || 'Error al procesar la solicitud' });
            }
          }

          // Si la respuesta es exitosa, leer como texto
          const text = await response.text();
          // Verificar si la respuesta es realmente un error disfrazado
          if (text && text.includes('errors')) {
            try {
              const errorData = JSON.parse(text);
              return Promise.reject(errorData);
            } catch (e) {
              // No es JSON, continuar normalmente
            }
          }
          return text;
        })
        .then(data => {
          const offCanvasEl = document.getElementById('offcanvasAddCliente');
          const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
          bsOffCanvas.hide();

          if (dt_cliente) {
            dt_cliente.draw();
          }

          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data === 'Created' ? 'El cliente ha sido creado.' : 'El cliente ha sido actualizado.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          addNewClienteForm.reset();
          $('#add-cliente-tipo-identificacion').val('').trigger('change');
        })
        .catch(error => {
          let errorMessage = 'No se pudo guardar el cliente.';

          // Manejar errores de validación de Laravel
          // Laravel retorna errores en formato: { message: "...", errors: { campo: [mensajes] } }
          if (error.errors && typeof error.errors === 'object') {
            const errorFields = Object.keys(error.errors);
            if (errorFields.length > 0) {
              const firstField = errorFields[0];
              const fieldErrors = error.errors[firstField];
              if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
                errorMessage = fieldErrors[0];
              } else if (typeof fieldErrors === 'string') {
                errorMessage = fieldErrors;
              }
            }
          } else if (error.message) {
            errorMessage = error.message;
          } else if (typeof error === 'object') {
            // Intentar encontrar mensajes de error comunes (formato alternativo)
            if (error.numero_identificacion) {
              errorMessage = Array.isArray(error.numero_identificacion)
                ? error.numero_identificacion[0]
                : error.numero_identificacion;
            } else if (error.correo_electronico) {
              errorMessage = Array.isArray(error.correo_electronico)
                ? error.correo_electronico[0]
                : error.correo_electronico;
            } else if (error.nombre) {
              errorMessage = Array.isArray(error.nombre) ? error.nombre[0] : error.nombre;
            } else if (error.tipo_identificacion) {
              errorMessage = Array.isArray(error.tipo_identificacion)
                ? error.tipo_identificacion[0]
                : error.tipo_identificacion;
            }
          }

          // Traducir mensajes de error comunes del inglés al español
          if (errorMessage && typeof errorMessage === 'string') {
            // Traducir mensaje de número de identificación duplicado
            if (
              errorMessage.toLowerCase().includes('numero identificacion') &&
              errorMessage.toLowerCase().includes('already been taken')
            ) {
              errorMessage = 'El número de identificación ya existe';
            } else if (
              errorMessage.toLowerCase().includes('numero_identificacion') &&
              errorMessage.toLowerCase().includes('already been taken')
            ) {
              errorMessage = 'El número de identificación ya existe';
            } else if (
              errorMessage.toLowerCase().includes('identification') &&
              errorMessage.toLowerCase().includes('already been taken')
            ) {
              errorMessage = 'El número de identificación ya existe';
            }
          }

          Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        });
    });
  }
});
