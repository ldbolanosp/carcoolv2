/**
 * Page Modelos management
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_modelo_table = document.querySelector('.datatables-modelos'),
    offCanvasForm = document.getElementById('offcanvasAddModelo');

  // Declare dt_modelo in outer scope
  let dt_modelo;

  // Select2 initialization
  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar marca',
      dropdownParent: $this.parent()
    });
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Modelos datatable
  if (dt_modelo_table) {
    dt_modelo = new DataTable(dt_modelo_table, {
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'configuracion/modelos/list',
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
        { data: 'marca_nombre' },
        { data: 'nombre' },
        { data: 'activo' },
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
            const marcaNombre = full['marca_nombre'] || '';
            return `<span class="fw-medium">${marcaNombre}</span>`;
          }
        },
        {
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const { nombre } = full;
            return `<span class="fw-medium">${nombre}</span>`;
          }
        },
        {
          targets: 4,
          className: 'text-center',
          render: function (data, type, full, meta) {
            const activo = full['activo'];
            return activo
              ? '<span class="badge bg-label-success">Activo</span>'
              : '<span class="badge bg-label-danger">Inactivo</span>';
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
              `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddModelo"><i class="icon-base ti tabler-edit icon-22px"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record" data-id="${full['id']}"><i class="icon-base ti tabler-trash icon-22px"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[3, 'asc']],
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
                placeholder: 'Buscar modelo',
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
        const modelo_id = deleteBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        Swal.fire({
          title: '¿Estás seguro?',
          text: "¡No podrás revertir esto!",
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
            fetch(`${baseUrl}configuracion/modelos/${modelo_id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
              }
            })
              .then(response => {
                if (response.ok) {
                  if (dt_modelo) {
                    dt_modelo.draw();
                  }
                  Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El modelo ha sido eliminado.',
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
                  text: 'No se pudo eliminar el modelo.',
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
        const modelo_id = editBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        document.getElementById('offcanvasAddModeloLabel').innerHTML = 'Editar Modelo';

        fetch(`${baseUrl}configuracion/modelos/${modelo_id}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('modelo_id').value = data.id;
            $('#add-modelo-marca').val(data.marca_id).trigger('change');
            document.getElementById('add-modelo-nombre').value = data.nombre;
            document.getElementById('add-modelo-activo').checked = data.activo;
          });
      }
    });

    // changing the title
    const addNewBtn = document.querySelector('.add-new');
    if (addNewBtn) {
      addNewBtn.addEventListener('click', function () {
        document.getElementById('modelo_id').value = '';
        $('#add-modelo-marca').val('').trigger('change');
        document.getElementById('add-modelo-nombre').value = '';
        document.getElementById('add-modelo-activo').checked = true;
        document.getElementById('offcanvasAddModeloLabel').innerHTML = 'Agregar Modelo';
      });
    }
  }

  // validating form and updating modelo's data
  const addNewModeloForm = document.getElementById('addNewModeloForm');

  if (addNewModeloForm) {
    const fv = FormValidation.formValidation(addNewModeloForm, {
      fields: {
        marca_id: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione una marca'
            }
          }
        },
        nombre: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el nombre del modelo'
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
      }
    }).on('core.form.valid', function () {
      const formData = new FormData(addNewModeloForm);
      const formDataObj = {};

      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });

      // Handle checkbox
      formDataObj['activo'] = document.getElementById('add-modelo-activo').checked ? 1 : 0;

      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(formDataObj)) {
        searchParams.append(key, value);
      }

      fetch(`${baseUrl}configuracion/modelos`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: searchParams.toString()
      })
        .then(response => {
          if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
          }
          return response.text();
        })
        .then(data => {
          const offCanvasEl = document.getElementById('offcanvasAddModelo');
          const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
          bsOffCanvas.hide();

          if (dt_modelo) {
            dt_modelo.draw();
          }

          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data === 'Created' ? 'El modelo ha sido creado.' : 'El modelo ha sido actualizado.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          addNewModeloForm.reset();
          $('#add-modelo-marca').val('').trigger('change');
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo guardar el modelo.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        });
    });
  }
});
