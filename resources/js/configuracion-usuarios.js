/**
 * Configuración Usuarios
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  // Variable declaration for table
  const dt_user_table = document.querySelector('.datatables-users'),
    offCanvasForm = document.getElementById('offcanvasAddUser');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_user_table) {
    const dt_user = new DataTable(dt_user_table, {
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'configuracion/usuarios/list',
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
        { data: 'name' },
        { data: 'email' },
        { data: 'role' }, // Added role column
        { data: 'email_verified_at' },
        { data: 'action' }
      ],
      columnDefs: [
        {
          // For Responsive
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
          // User full name
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            const { name } = full;
            const stateNum = Math.floor(Math.random() * 6);
            const states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
            const state = states[stateNum];
            const initials = (name.match(/\b\w/g) || []).shift() + (name.match(/\b\w/g) || []).pop();
            const initialsUpper = initials.toUpperCase();
            const avatar = `<span class="avatar-initial rounded-circle bg-label-${state}">${initialsUpper}</span>`;

            return `
              <div class="d-flex justify-content-start align-items-center user-name">
                <div class="avatar-wrapper">
                  <div class="avatar avatar-sm me-4">
                    ${avatar}
                  </div>
                </div>
                <div class="d-flex flex-column">
                  <span class="fw-medium text-heading">${name}</span>
                </div>
              </div>
            `;
          }
        },
        {
          // User email
          targets: 3,
          render: function (data, type, full, meta) {
            return '<span class="user-email">' + full['email'] + '</span>';
          }
        },
        {
          // User role
          targets: 4,
          render: function (data, type, full, meta) {
            return '<span class="text-heading">' + (full['role'] || '') + '</span>';
          }
        },
        {
          // email verify
          targets: 5,
          className: 'text-center',
          render: function (data, type, full, meta) {
            const verified = full['email_verified_at'];
            return `${
              verified
                ? '<i class="icon-base ti fs-4 tabler-shield-check text-success"></i>'
                : '<i class="icon-base ti fs-4 tabler-shield-x text-danger" ></i>'
            }`;
          }
        },
        {
          // Actions
          targets: -1,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-4">' +
              `<button class="btn btn-sm btn-icon edit-record" data-id="${full['id']}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser"><i class="icon-base ti tabler-edit icon-22px"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record" data-id="${full['id']}"><i class="icon-base ti tabler-trash icon-22px"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[2, 'desc']],
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
                placeholder: 'Buscar Usuario',
                text: '_INPUT_'
              }
            },
            {
              buttons: [
                {
                  text: '<i class="icon-base ti tabler-plus icon-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Agregar Usuario</span>',
                  className: 'add-new btn btn-primary',
                  attr: {
                    'data-bs-toggle': 'offcanvas',
                    'data-bs-target': '#offcanvasAddUser'
                  }
                }
              ]
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: [
            {
              info: {
                text: 'Mostrando _START_ a _END_ de _TOTAL_ entradas'
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
              return 'Detalles de ' + data['name'];
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
      },
      initComplete: function () {
        document.querySelectorAll('.dt-buttons .btn').forEach(btn => {
          btn.classList.remove('btn-secondary');
        });
      }
    });

    // Delete Record
    document.addEventListener('click', function (e) {
      if (e.target.closest('.delete-record')) {
        const deleteBtn = e.target.closest('.delete-record');
        const user_id = deleteBtn.dataset.id;
        
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
            fetch(`${baseUrl}configuracion/usuarios/${user_id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
              }
            })
              .then(response => {
                if (response.ok) {
                  dt_user.draw();
                  Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El usuario ha sido eliminado.',
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
              });
          }
        });
      }
    });

    // edit record
    document.addEventListener('click', function (e) {
      if (e.target.closest('.edit-record')) {
        const editBtn = e.target.closest('.edit-record');
        const user_id = editBtn.dataset.id;

        document.getElementById('offcanvasAddUserLabel').innerHTML = 'Editar Usuario';

        fetch(`${baseUrl}configuracion/usuarios/${user_id}`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('user_id').value = data.id;
            document.getElementById('add-user-fullname').value = data.name;
            document.getElementById('add-user-email').value = data.email;
            
            // Set role in select
            if (data.roles && data.roles.length > 0) {
                document.getElementById('user-role').value = data.roles[0].name;
            } else {
                document.getElementById('user-role').value = '';
            }
          });
      }
    });

    // changing the title
    const addNewBtn = document.querySelector('.add-new');
    if (addNewBtn) {
      addNewBtn.addEventListener('click', function () {
        document.getElementById('user_id').value = ''; 
        document.getElementById('offcanvasAddUserLabel').innerHTML = 'Agregar Usuario';
        document.getElementById('user-role').value = ''; // Reset role select
      });
    }

    // Filter form control to default size
    setTimeout(() => {
      const elementsToModify = [
        { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
        { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
        { selector: '.dt-length .form-select', classToRemove: 'form-select-sm', classToAdd: 'ms-0' },
        { selector: '.dt-length', classToAdd: 'mb-md-6 mb-0' },
        {
          selector: '.dt-layout-end',
          classToRemove: 'justify-content-between',
          classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-2 flex-wrap'
        },
        { selector: '.dt-buttons', classToAdd: 'd-flex gap-4 mb-md-0 mb-4' },
        { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
        { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
      ];

      elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
        document.querySelectorAll(selector).forEach(element => {
          if (classToRemove) {
            classToRemove.split(' ').forEach(className => element.classList.remove(className));
          }
          if (classToAdd) {
            classToAdd.split(' ').forEach(className => element.classList.add(className));
          }
        });
      });
    }, 100);
  }

  // validating form and updating user's data
  const addNewUserForm = document.getElementById('addNewUserForm');

  if (addNewUserForm) {
    const fv = FormValidation.formValidation(addNewUserForm, {
      fields: {
        name: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el nombre completo'
            }
          }
        },
        email: {
          validators: {
            notEmpty: {
              message: 'Por favor ingrese el email'
            },
            emailAddress: {
              message: 'El valor no es un email válido'
            }
          }
        },
        role: {
            validators: {
                notEmpty: {
                    message: 'Por favor seleccione un rol'
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
      const formData = new FormData(addNewUserForm);
      const formDataObj = {};

      formData.forEach((value, key) => {
        formDataObj[key] = value;
      });

      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(formDataObj)) {
        searchParams.append(key, value);
      }

      fetch(`${baseUrl}configuracion/usuarios`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: searchParams.toString()
      })
        .then(response => {
          if (!response.ok) {
            return response.text().then(text => { throw new Error(text) });
          }
          return response.text();
        })
        .then(status => {
          dt_user_table && new DataTable(dt_user_table).draw();
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offCanvasForm);
          offcanvasInstance && offcanvasInstance.hide();

          Swal.fire({
            icon: 'success',
            title: `¡Éxito!`,
            text: `Usuario guardado correctamente.`,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        })
        .catch(err => {
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offCanvasForm);
          offcanvasInstance && offcanvasInstance.hide();

          Swal.fire({
            title: 'Error!',
            text: 'Ocurrió un error al guardar. Verifica que el email no esté duplicado.',
            icon: 'error',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        });
    });

    offCanvasForm.addEventListener('hidden.bs.offcanvas', function () {
      fv.resetForm(true);
    });
  }
});
