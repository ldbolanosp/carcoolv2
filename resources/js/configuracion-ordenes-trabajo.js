/**
 * Page Ordenes Trabajo management
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_orden_trabajo_table = document.querySelector('.datatables-ordenes-trabajo'),
    offCanvasForm = document.getElementById('offcanvasAddOrdenTrabajo');

  // Get user permissions from global variable or DOM
  // This assumes permissions are somehow available, or we'll assume false safely
  // In a real app, consider passing this via a global JS object in the view
  // For now, buttons might show but backend will block if permission missing,
  // or we rely on server-side rendering to hide buttons in the 'actions' column.
  // However, the 'Add New' button is often static in DOM.

  // Declare dt_orden_trabajo in outer scope
  let dt_orden_trabajo;

  // Select2 initialization - Initialize after offcanvas is shown
  function initSelect2() {
    const offcanvasEl = document.getElementById('offcanvasAddOrdenTrabajo');
    if (offcanvasEl) {
      const select2Elements = $(offcanvasEl).find('.select2');
      select2Elements.each(function () {
        const $this = $(this);
        if (!$this.hasClass('select2-hidden-accessible')) {
          $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Seleccionar opción',
            dropdownParent: offcanvasEl
          });
        }
      });
    }
  }

  // Initialize select2 when offcanvas is shown
  const offcanvasEl = document.getElementById('offcanvasAddOrdenTrabajo');
  if (offcanvasEl) {
    offcanvasEl.addEventListener('shown.bs.offcanvas', function () {
      initSelect2();
    });
  }

  // Also initialize on page load for any select2 outside offcanvas
  var select2 = $('.select2').not('#offcanvasAddOrdenTrabajo .select2');
  if (select2.length) {
    select2.each(function () {
      const $this = $(this);
      if (!$this.hasClass('select2-hidden-accessible')) {
        $this.wrap('<div class="position-relative"></div>').select2({
          placeholder: 'Seleccionar opción',
          dropdownParent: $this.parent()
        });
      }
    });
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Ordenes Trabajo datatable
  if (dt_orden_trabajo_table) {
    dt_orden_trabajo = new DataTable(dt_orden_trabajo_table, {
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'ordenes-trabajo/list',
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
        { data: 'tipo_orden' },
        { data: 'cliente_nombre' },
        { data: 'vehiculo_placa' },
        { data: 'motivo_ingreso' },
        { data: 'km_actual' },
        { data: 'etapa_actual' },
        { data: 'created_at' },
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
            const tipo = full['tipo_orden'] || '';
            const badgeClass = tipo === 'Taller' ? 'bg-label-primary' : 'bg-label-info';
            return `<span class="badge ${badgeClass}">${tipo}</span>`;
          }
        },
        {
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const nombre = full['cliente_nombre'] || '';
            return `<span class="fw-medium">${nombre}</span>`;
          }
        },
        {
          targets: 4,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const placa = full['vehiculo_placa'] || '';
            const info = full['vehiculo_info'] || '';
            return `<span class="fw-medium">${placa}</span><br><small class="text-muted">${info}</small>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            const motivo = full['motivo_ingreso'] || '';
            const truncated = motivo.length > 50 ? motivo.substring(0, 50) + '...' : motivo;
            return `<span title="${motivo}">${truncated || '-'}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            const km = full['km_actual'];
            return km ? `<span>${km.toLocaleString()} km</span>` : '<span>-</span>';
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            const etapa = full['etapa_actual'] || '';
            return `<span class="badge bg-label-warning">${etapa}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            const fecha = full['created_at'] || '';
            return `<span>${fecha}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            // Use global userPermissions object if available (injected in Blade)
            const canCreate = typeof userPermissions !== 'undefined' && userPermissions.canCreate;
            // Assuming Edit is tied to Create/Update permission for now
            const canDelete = typeof userPermissions !== 'undefined' && userPermissions.canDelete;

            let buttons = '<div class="d-flex align-items-center gap-4">';
            buttons += `<a href="${baseUrl}ordenes-trabajo/${full['id']}/detalle" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-eye icon-22px"></i></a>`;

            if (canCreate) {
              buttons += `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddOrdenTrabajo"><i class="icon-base ti tabler-edit icon-22px"></i></button>`;
            }

            if (canDelete) {
              buttons += `<button class="btn btn-sm btn-icon delete-record" data-id="${full['id']}"><i class="icon-base ti tabler-trash icon-22px"></i></button>`;
            }

            buttons += '</div>';
            return buttons;
          }
        }
      ],
      order: [[1, 'desc']],
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
                placeholder: 'Buscar orden de trabajo',
                text: '_INPUT_'
              }
            },
            {
              // Only show Add button if user can create
              buttons:
                typeof userPermissions !== 'undefined' && userPermissions.canCreate
                  ? [
                      {
                        text: '<i class="icon-base ti tabler-plus icon-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Agregar Orden</span>',
                        className: 'add-new btn btn-primary',
                        attr: {
                          'data-bs-toggle': 'offcanvas',
                          'data-bs-target': '#offcanvasAddOrdenTrabajo'
                        }
                      }
                    ]
                  : []
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
              return 'Detalles de Orden #' + data['id'];
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
        const orden_id = deleteBtn.dataset.id;
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
            fetch(`${baseUrl}ordenes-trabajo/${orden_id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
              }
            })
              .then(response => {
                if (response.ok) {
                  if (dt_orden_trabajo) {
                    dt_orden_trabajo.draw();
                  }
                  Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'La orden de trabajo ha sido eliminada.',
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
                  text: 'No se pudo eliminar la orden de trabajo.',
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
        const orden_id = editBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        document.getElementById('offcanvasAddOrdenTrabajoLabel').innerHTML = 'Editar Orden de Trabajo';

        fetch(`${baseUrl}ordenes-trabajo/${orden_id}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('orden_trabajo_id').value = data.id;
            $('#add-orden-tipo').val(data.tipo_orden).trigger('change');
            $('#add-orden-cliente').val(data.cliente_id).trigger('change');
            $('#add-orden-vehiculo').val(data.vehiculo_id).trigger('change');
            document.getElementById('add-orden-motivo').value = data.motivo_ingreso;
            document.getElementById('add-orden-km').value = data.km_actual || '';
            // La etapa no se edita desde el formulario, se maneja automáticamente
          });
      }
    });

    // changing the title
    const addNewBtn = document.querySelector('.add-new');
    if (addNewBtn) {
      addNewBtn.addEventListener('click', function () {
        document.getElementById('orden_trabajo_id').value = '';
        $('#add-orden-tipo').val('').trigger('change');
        $('#add-orden-cliente').val('').trigger('change');
        $('#add-orden-vehiculo').val('').trigger('change');
        document.getElementById('add-orden-motivo').value = '';
        document.getElementById('add-orden-km').value = '';
        // La etapa se establece automáticamente en el backend
        document.getElementById('offcanvasAddOrdenTrabajoLabel').innerHTML = 'Agregar Orden de Trabajo';
      });
    }
  }

  // validating form and updating orden trabajo's data
  const addNewOrdenTrabajoForm = document.getElementById('addNewOrdenTrabajoForm');

  if (addNewOrdenTrabajoForm) {
    const fv = FormValidation.formValidation(addNewOrdenTrabajoForm, {
      fields: {
        tipo_orden: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione el tipo de orden'
            }
          }
        },
        cliente_id: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione un cliente'
            }
          }
        },
        vehiculo_id: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione un vehículo'
            }
          }
        },
        motivo_ingreso: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el motivo de ingreso'
            }
          }
        },
        km_actual: {
          validators: {
            integer: {
              message: 'El kilometraje debe ser un número entero'
            },
            greaterThan: {
              min: 0,
              message: 'El kilometraje debe ser mayor o igual a 0'
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
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
          //* Move the error message out of the `row` element for custom-options
          if (e.element.parentElement.parentElement.classList.contains('custom-option')) {
            e.element.closest('.row').insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    }).on('core.form.valid', function () {
      const formData = new FormData(addNewOrdenTrabajoForm);
      const formDataObj = {};

      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });

      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(formDataObj)) {
        searchParams.append(key, value);
      }

      fetch(`${baseUrl}ordenes-trabajo`, {
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
            if (isJson) {
              const errorData = await response.json();
              return Promise.reject(errorData);
            } else {
              const text = await response.text();
              return Promise.reject({ message: text || 'Error al procesar la solicitud' });
            }
          }

          const text = await response.text();
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
          const offCanvasEl = document.getElementById('offcanvasAddOrdenTrabajo');
          const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
          bsOffCanvas.hide();

          if (dt_orden_trabajo) {
            dt_orden_trabajo.draw();
          }

          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text:
              data === 'Created' ? 'La orden de trabajo ha sido creada.' : 'La orden de trabajo ha sido actualizada.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          addNewOrdenTrabajoForm.reset();
          $('#add-orden-tipo').val('').trigger('change');
          $('#add-orden-cliente').val('').trigger('change');
          $('#add-orden-vehiculo').val('').trigger('change');
          // La etapa se establece automáticamente en el backend
        })
        .catch(error => {
          let errorMessage = 'No se pudo guardar la orden de trabajo.';

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
