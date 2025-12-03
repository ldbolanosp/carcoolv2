@extends('layouts/layoutMaster')

@section('title', 'Modelos - Configuración')

<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
@vite(['resources/js/configuracion-modelos.js'])
@endsection

@section('content')
<!-- Modelos List Table -->
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Modelos de Vehículos</h5>
    <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddModelo">
      <i class="icon-base ti tabler-plus icon-sm me-2"></i>
      <span>Agregar Modelo</span>
    </button>
  </div>
  <div class="card-datatable">
    <table class="datatables-modelos table border-top">
      <thead>
        <tr>
          <th></th>
          <th>Id</th>
          <th>Marca</th>
          <th>Nombre</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
  <!-- Offcanvas to add new modelo -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddModelo" aria-labelledby="offcanvasAddModeloLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddModeloLabel" class="offcanvas-title">Agregar Modelo</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-modelo pt-0" id="addNewModeloForm">
        <input type="hidden" name="id" id="modelo_id">
        <div class="mb-6 form-control-validation">
          <label class="form-label" for="add-modelo-marca">Marca</label>
          <select id="add-modelo-marca" class="select2 form-select" name="marca_id">
            <option value="">Seleccionar marca</option>
            @foreach($marcas as $marca)
            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-6 form-control-validation">
          <label class="form-label" for="add-modelo-nombre">Nombre del Modelo</label>
          <input type="text" class="form-control" id="add-modelo-nombre" placeholder="Ej: Corolla" name="nombre"
            aria-label="Nombre del modelo" />
        </div>
        <div class="mb-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="add-modelo-activo" name="activo" checked>
            <label class="form-check-label" for="add-modelo-activo">Activo</label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary me-3 data-submit">Guardar</button>
        <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancelar</button>
      </form>
    </div>
  </div>
</div>

@endsection
