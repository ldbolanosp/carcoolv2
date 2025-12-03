@extends('layouts/layoutMaster')

@section('title', 'Marcas - Configuración')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/js/configuracion-marcas.js'])
@endsection

@section('content')
  <!-- Marcas List Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Marcas de Vehículos</h5>
      <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddMarca">
        <i class="icon-base ti tabler-plus icon-sm me-2"></i>
        <span>Agregar Marca</span>
      </button>
    </div>
    <div class="card-datatable">
      <table class="datatables-marcas table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Id</th>
            <th>Nombre</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
    <!-- Offcanvas to add new marca -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddMarca" aria-labelledby="offcanvasAddMarcaLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddMarcaLabel" class="offcanvas-title">Agregar Marca</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-marca pt-0" id="addNewMarcaForm">
          <input type="hidden" name="id" id="marca_id">
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-marca-nombre">Nombre de la Marca</label>
            <input type="text" class="form-control" id="add-marca-nombre" placeholder="Ej: Toyota" name="nombre"
              aria-label="Nombre de la marca" />
          </div>
          <div class="mb-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="add-marca-activo" name="activo" checked>
              <label class="form-check-label" for="add-marca-activo">Activo</label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Guardar</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancelar</button>
        </form>
      </div>
    </div>
  </div>

@endsection
