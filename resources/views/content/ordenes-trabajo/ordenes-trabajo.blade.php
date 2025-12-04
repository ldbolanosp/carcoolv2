@extends('layouts/layoutMaster')

@section('title', 'Órdenes de Trabajo')

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
  @vite(['resources/js/configuracion-ordenes-trabajo.js'])
@endsection

@section('content')
  <!-- Ordenes Trabajo List Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Órdenes de Trabajo</h5>
      <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasAddOrdenTrabajo">
        <i class="icon-base ti tabler-plus icon-sm me-2"></i>
        <span>Agregar Orden</span>
      </button>
    </div>
    <div class="card-datatable">
      <table class="datatables-ordenes-trabajo table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Id</th>
            <th>Tipo</th>
            <th>Espacio</th>
            <th>Cliente</th>
            <th>Vehículo</th>
            <th>Motivo</th>
            <th>Km Actual</th>
            <th>Etapa</th>
            <th>Fecha Creación</th>
            <th>Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
    <!-- Offcanvas to add new orden trabajo -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddOrdenTrabajo"
      aria-labelledby="offcanvasAddOrdenTrabajoLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddOrdenTrabajoLabel" class="offcanvas-title">Agregar Orden de Trabajo</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-orden-trabajo pt-0" id="addNewOrdenTrabajoForm">
          <input type="hidden" name="id" id="orden_trabajo_id">
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-orden-tipo">Tipo de Orden <span class="text-danger">*</span></label>
            <select id="add-orden-tipo" class="select2 form-select" name="tipo_orden">
              <option value="">Seleccionar tipo</option>
              <option value="Taller">Taller</option>
              <option value="Domicilio">Domicilio</option>
            </select>
          </div>
          <div class="mb-6 form-control-validation" id="espacio-trabajo-container" style="display: none;">
            <label class="form-label" for="add-orden-espacio">Espacio de Trabajo <span class="text-danger">*</span></label>
            <select id="add-orden-espacio" class="select2 form-select" name="espacio_trabajo">
              <option value="">Seleccionar espacio</option>
              @for ($i = 1; $i <= 16; $i++)
                <option value="{{ $i }}" {{ in_array($i, $espaciosDisponibles) ? '' : 'disabled' }}>
                  Espacio {{ $i }} {{ in_array($i, $espaciosDisponibles) ? '' : '(Ocupado)' }}
                </option>
              @endfor
            </select>
            <div class="form-text">
              <span class="text-success">{{ count($espaciosDisponibles) }}</span> de 16 espacios disponibles
            </div>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-orden-cliente">Cliente <span class="text-danger">*</span></label>
            <select id="add-orden-cliente" class="select2 form-select" name="cliente_id">
              <option value="">Seleccionar cliente</option>
              @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}">{{ $cliente->nombre }} - {{ $cliente->numero_identificacion }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-orden-vehiculo">Vehículo <span class="text-danger">*</span></label>
            <select id="add-orden-vehiculo" class="select2 form-select" name="vehiculo_id">
              <option value="">Seleccionar vehículo</option>
              @foreach ($vehiculos as $vehiculo)
                <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->marca->nombre ?? '' }}
                  {{ $vehiculo->modelo->nombre ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-orden-motivo">Motivo de Ingreso <span class="text-danger">*</span></label>
            <textarea class="form-control" id="add-orden-motivo" rows="3"
              placeholder="Describa el motivo de ingreso del vehículo" name="motivo_ingreso" aria-label="Motivo de ingreso"></textarea>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-orden-km">Km Actual</label>
            <input type="number" class="form-control" id="add-orden-km" placeholder="Ej: 50000" name="km_actual"
              aria-label="Kilometraje actual" min="0" />
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Guardar</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancelar</button>
        </form>
      </div>
    </div>
  </div>

@endsection
