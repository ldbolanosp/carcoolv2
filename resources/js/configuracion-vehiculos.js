/**
 * Page Vehiculos management
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_vehiculo_table = document.querySelector('.datatables-vehiculos'),
    offCanvasForm = document.getElementById('offcanvasAddVehiculo');

  // Declare dt_vehiculo in outer scope
  let dt_vehiculo;
  let colorPicker;

  // Select2 initialization
  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar opción',
      dropdownParent: $this.parent()
    });
  }

  // Color picker initialization
  const colorPickerEl = document.getElementById('color-picker-vehiculo');
  if (colorPickerEl) {
    colorPicker = new Pickr({
      el: colorPickerEl,
      theme: 'nano',
      default: '#000000',
      defaultRepresentation: 'HEX',
      components: {
        preview: true,
        opacity: false,
        hue: true,
        interaction: {
          hex: true,
          rgba: true,
          input: true,
          clear: false,
          save: true
        }
      }
    });

    colorPicker.on('change', color => {
      const hexColor = color.toHEXA().toString();
      document.getElementById('add-vehiculo-color').value = hexColor;
    });

    colorPicker.on('save', color => {
      const hexColor = color.toHEXA().toString();
      document.getElementById('add-vehiculo-color').value = hexColor;
      colorPicker.hide();
    });
  }

  // Load modelos when marca changes
  $('#add-vehiculo-marca').on('change', function () {
    const marcaId = $(this).val();
    const modeloSelect = $('#add-vehiculo-modelo');

    modeloSelect.empty().append('<option value="">Seleccionar modelo</option>');

    if (marcaId) {
      fetch(`${baseUrl}vehiculos/modelos?marca_id=${marcaId}`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
        .then(response => response.json())
        .then(data => {
          data.forEach(modelo => {
            modeloSelect.append(`<option value="${modelo.id}">${modelo.nombre}</option>`);
          });
          modeloSelect.trigger('change');
        })
        .catch(error => {
          console.error('Error loading modelos:', error);
        });
    }
  });

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Vehiculos datatable
  if (dt_vehiculo_table) {
    dt_vehiculo = new DataTable(dt_vehiculo_table, {
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'vehiculos/list',
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
        { data: 'placa' },
        { data: 'marca_nombre' },
        { data: 'modelo_nombre' },
        { data: 'ano' },
        { data: 'color' },
        { data: 'numero_chasis' },
        { data: 'numero_unidad' },
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
            const { placa } = full;
            return `<span class="fw-medium">${placa}</span>`;
          }
        },
        {
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const marcaNombre = full['marca_nombre'] || '';
            return `<span class="fw-medium">${marcaNombre}</span>`;
          }
        },
        {
          targets: 4,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const modeloNombre = full['modelo_nombre'] || '';
            return `<span class="fw-medium">${modeloNombre}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.ano}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            const color = full['color'] || '#000000';
            return `<span class="badge" style="background-color: ${color}; color: ${getContrastColor(color)}; padding: 0.35rem 0.65rem;">${color}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            const chasis = full['numero_chasis'] || '';
            return `<span>${chasis || '-'}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            const unidad = full['numero_unidad'] || '';
            return `<span>${unidad || '-'}</span>`;
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
              `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddVehiculo"><i class="icon-base ti tabler-edit icon-22px"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record" data-id="${full['id']}"><i class="icon-base ti tabler-trash icon-22px"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[2, 'asc']],
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
                placeholder: 'Buscar vehículo',
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
              return 'Detalles de ' + data['placa'];
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
        const vehiculo_id = deleteBtn.dataset.id;
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
            fetch(`${baseUrl}vehiculos/${vehiculo_id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
              }
            })
              .then(response => {
                if (response.ok) {
                  if (dt_vehiculo) {
                    dt_vehiculo.draw();
                  }
                  Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El vehículo ha sido eliminado.',
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
                  text: 'No se pudo eliminar el vehículo.',
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
        const vehiculo_id = editBtn.dataset.id;
        const dtrModal = document.querySelector('.dtr-bs-modal.show');

        if (dtrModal) {
          const bsModal = bootstrap.Modal.getInstance(dtrModal);
          bsModal.hide();
        }

        document.getElementById('offcanvasAddVehiculoLabel').innerHTML = 'Editar Vehículo';

        fetch(`${baseUrl}vehiculos/${vehiculo_id}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('vehiculo_id').value = data.id;
            document.getElementById('add-vehiculo-placa').value = data.placa;
            $('#add-vehiculo-marca').val(data.marca_id).trigger('change');

            // Load modelos for the marca and then set the modelo
            setTimeout(() => {
              $('#add-vehiculo-modelo').val(data.modelo_id).trigger('change');
            }, 500);

            document.getElementById('add-vehiculo-ano').value = data.ano;
            document.getElementById('add-vehiculo-color').value = data.color || '#000000';
            if (colorPicker) {
              colorPicker.setColor(data.color || '#000000');
            }
            document.getElementById('add-vehiculo-chasis').value = data.numero_chasis || '';
            document.getElementById('add-vehiculo-unidad').value = data.numero_unidad || '';
          });
      }
    });

    // changing the title
    const addNewBtn = document.querySelector('.add-new');
    if (addNewBtn) {
      addNewBtn.addEventListener('click', function () {
        document.getElementById('vehiculo_id').value = '';
        document.getElementById('add-vehiculo-placa').value = '';
        $('#add-vehiculo-marca').val('').trigger('change');
        $('#add-vehiculo-modelo').val('').trigger('change');
        document.getElementById('add-vehiculo-ano').value = '';
        document.getElementById('add-vehiculo-color').value = '#000000';
        if (colorPicker) {
          colorPicker.setColor('#000000');
        }
        document.getElementById('add-vehiculo-chasis').value = '';
        document.getElementById('add-vehiculo-unidad').value = '';
        document.getElementById('offcanvasAddVehiculoLabel').innerHTML = 'Agregar Vehículo';
      });
    }
  }

  // Helper function to get contrast color
  function getContrastColor(hexColor) {
    const r = parseInt(hexColor.substr(1, 2), 16);
    const g = parseInt(hexColor.substr(3, 2), 16);
    const b = parseInt(hexColor.substr(5, 2), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 128 ? '#000000' : '#FFFFFF';
  }

  // validating form and updating vehiculo's data
  const addNewVehiculoForm = document.getElementById('addNewVehiculoForm');

  if (addNewVehiculoForm) {
    const fv = FormValidation.formValidation(addNewVehiculoForm, {
      fields: {
        placa: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese la placa del vehículo'
            }
          }
        },
        marca_id: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione una marca'
            }
          }
        },
        modelo_id: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione un modelo'
            }
          }
        },
        ano: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el año del vehículo'
            },
            integer: {
              message: 'El año debe ser un número entero'
            },
            between: {
              min: 1900,
              max: new Date().getFullYear() + 1,
              message: `El año debe estar entre 1900 y ${new Date().getFullYear() + 1}`
            }
          }
        },
        color: {
          validators: {
            notEmpty: {
              message: 'Por favor seleccione un color'
            },
            regexp: {
              regexp: /^#[0-9A-Fa-f]{6}$/,
              message: 'El color debe ser un código hexadecimal válido (ej: #FF0000)'
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
      const formData = new FormData(addNewVehiculoForm);
      const formDataObj = {};

      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });

      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(formDataObj)) {
        searchParams.append(key, value);
      }

      fetch(`${baseUrl}vehiculos`, {
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
          const offCanvasEl = document.getElementById('offcanvasAddVehiculo');
          const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
          bsOffCanvas.hide();

          if (dt_vehiculo) {
            dt_vehiculo.draw();
          }

          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data === 'Created' ? 'El vehículo ha sido creado.' : 'El vehículo ha sido actualizado.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          addNewVehiculoForm.reset();
          $('#add-vehiculo-marca').val('').trigger('change');
          $('#add-vehiculo-modelo').val('').trigger('change');
          if (colorPicker) {
            colorPicker.setColor('#000000');
          }
          document.getElementById('add-vehiculo-color').value = '#000000';
        })
        .catch(error => {
          let errorMessage = 'No se pudo guardar el vehículo.';
          if (error.message) {
            errorMessage = error.message;
          } else if (error.placa) {
            errorMessage = error.placa[0];
          } else if (error.numero_chasis) {
            errorMessage = error.numero_chasis[0];
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        });
    });
  }
});
