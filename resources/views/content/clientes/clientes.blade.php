@extends('layouts/layoutMaster')

@section('title', 'Clientes')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/js/configuracion-clientes.js'])

@endsection

@section('content')
  <!-- Clientes List Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Clientes</h5>
      <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddCliente">
        <i class="icon-base ti tabler-plus icon-sm me-2"></i>
        <span>Agregar Cliente</span>
      </button>
    </div>
    <div class="card-datatable">
      <table class="datatables-clientes table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Id</th>
            <th>Tipo Identificación</th>
            <th>Número Identificación</th>
            <th>Nombre</th>
            <th>Correo Electrónico</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
    <!-- Offcanvas to add new cliente -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddCliente"
      aria-labelledby="offcanvasAddClienteLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddClienteLabel" class="offcanvas-title">Agregar Cliente</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-cliente pt-0" id="addNewClienteForm">
          <input type="hidden" name="id" id="cliente_id">
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-cliente-tipo-identificacion">Tipo de Identificación <span
                class="text-danger">*</span></label>
            <select id="add-cliente-tipo-identificacion" class="select2 form-select" name="tipo_identificacion">
              <option value="">Seleccionar tipo</option>
              <option value="Física">Física</option>
              <option value="Jurídica">Jurídica</option>
              <option value="DIMEX">DIMEX</option>
              <option value="NITE">NITE</option>
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <div class="form-numero-identificacion">
              <label class="form-label" for="add-cliente-numero-identificacion">Número de Identificación <span
                  class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <input type="text" class="form-control" id="add-cliente-numero-identificacion"
                  placeholder="Ej: 1-2345-6789" name="numero_identificacion" aria-label="Número de identificación" />
                <button type="button" class="btn btn-outline-primary" id="btn-buscar-alegra" title="Buscar en Alegra">
                  <i class="icon-base ti tabler-search icon-sm"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-cliente-nombre">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="add-cliente-nombre" placeholder="Ej: Juan Pérez" name="nombre"
              aria-label="Nombre del cliente" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-cliente-correo">Correo Electrónico</label>
            <input type="email" class="form-control" id="add-cliente-correo" placeholder="Ej: juan@example.com"
              name="correo_electronico" aria-label="Correo electrónico" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-cliente-telefono">Teléfono</label>
            <input type="text" class="form-control" id="add-cliente-telefono" placeholder="Ej: 8888-8888"
              name="telefono" aria-label="Teléfono" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-cliente-direccion">Dirección</label>
            <textarea class="form-control" id="add-cliente-direccion" rows="3" placeholder="Ej: San José, Costa Rica"
              name="direccion" aria-label="Dirección"></textarea>
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Guardar</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancelar</button>
        </form>
      </div>
    </div>
  </div>

@endsection
