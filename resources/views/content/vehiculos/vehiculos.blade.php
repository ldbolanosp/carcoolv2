@extends('layouts/layoutMaster')

@section('title', 'Vehículos')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/pickr/pickr-themes.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/pickr/pickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/js/configuracion-vehiculos.js'])
@endsection

@section('content')
  <!-- Vehiculos List Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Vehículos</h5>
      <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasAddVehiculo">
        <i class="icon-base ti tabler-plus icon-sm me-2"></i>
        <span>Agregar Vehículo</span>
      </button>
    </div>
    <div class="card-datatable">
      <table class="datatables-vehiculos table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Id</th>
            <th>Placa</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Año</th>
            <th>Color</th>
            <th>Chasis</th>
            <th>Unidad</th>
            <th>Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
    <!-- Offcanvas to add new vehiculo -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddVehiculo"
      aria-labelledby="offcanvasAddVehiculoLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddVehiculoLabel" class="offcanvas-title">Agregar Vehículo</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-vehiculo pt-0" id="addNewVehiculoForm">
          <input type="hidden" name="id" id="vehiculo_id">
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-placa">Placa <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="add-vehiculo-placa" placeholder="Ej: ABC-123" name="placa"
              aria-label="Placa del vehículo" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-marca">Marca <span class="text-danger">*</span></label>
            <select id="add-vehiculo-marca" class="select2 form-select" name="marca_id">
              <option value="">Seleccionar marca</option>
              @foreach ($marcas as $marca)
                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-modelo">Modelo <span class="text-danger">*</span></label>
            <select id="add-vehiculo-modelo" class="select2 form-select" name="modelo_id">
              <option value="">Seleccionar modelo</option>
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-ano">Año <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="add-vehiculo-ano" placeholder="Ej: 2020" name="ano"
              aria-label="Año del vehículo" min="1900" max="{{ date('Y') + 1 }}" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-color">Color <span class="text-danger">*</span></label>
            <div id="color-picker-vehiculo"></div>
            <input type="hidden" id="add-vehiculo-color" name="color" value="#000000" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-chasis">Número de Chasis</label>
            <input type="text" class="form-control" id="add-vehiculo-chasis" placeholder="Ej: 1234567890"
              name="numero_chasis" aria-label="Número de chasis" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-vehiculo-unidad">Número de Unidad</label>
            <input type="text" class="form-control" id="add-vehiculo-unidad" placeholder="Ej: UN-001"
              name="numero_unidad" aria-label="Número de unidad" />
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Guardar</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancelar</button>
        </form>
      </div>
    </div>
  </div>

@endsection
