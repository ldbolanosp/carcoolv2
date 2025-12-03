@extends('layouts/layoutMaster')

@section('title', 'Calendario de Órdenes de Trabajo')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/app-calendar.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/jquery/jquery.js',
  'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('page-script')
@vite(['resources/js/calendario.js'])
@endsection

@section('content')
<div class="card app-calendar-wrapper">
  <div class="row g-0">
    <!-- Calendar Sidebar -->
    <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
      <div class="p-3 pb-2">
        <h5>Filtros</h5>
        <div class="mb-3">
          <label for="filter-client" class="form-label">Cliente</label>
          <select id="filter-client" class="select2 form-select">
            <option value="">Todos los clientes</option>
            @foreach($clientes as $cliente)
              <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <!-- /Calendar Sidebar -->

    <!-- Calendar -->
    <div class="col app-calendar-content">
      <div class="card shadow-none border-0">
        <div class="card-body pb-0">
          <!-- FullCalendar -->
          <div id="calendar"></div>
        </div>
      </div>
      <div class="app-overlay"></div>
    </div>
    <!-- /Calendar -->
  </div>
</div>

<!-- Modal Resumen Orden -->
<div class="modal fade" id="modalOrdenTrabajo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-transparent">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pb-5 px-sm-5 pt-50">
        <div class="text-center mb-2">
          <h3 class="mb-1">Resumen Orden de Trabajo #<span id="modal-orden-id"></span></h3>
          <p class="text-muted">Detalles generales de la orden</p>
        </div>
        
        <div class="row mt-4">
          <!-- Cliente y Vehículo -->
          <div class="col-md-6 mb-3">
            <div class="card h-100 bg-lighter border-0">
              <div class="card-body">
                 <h5 class="card-title mb-3"><i class="ti tabler-user me-2"></i>Cliente</h5>
                 <p class="mb-1"><strong>Nombre:</strong> <span id="modal-cliente-nombre"></span></p>
                 <p class="mb-0"><strong>Teléfono:</strong> <span id="modal-cliente-telefono"></span></p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="card h-100 bg-lighter border-0">
              <div class="card-body">
                 <h5 class="card-title mb-3"><i class="ti tabler-car me-2"></i>Vehículo</h5>
                 <p class="mb-1"><strong>Placa:</strong> <span id="modal-vehiculo-placa"></span></p>
                 <p class="mb-1"><strong>Marca/Modelo:</strong> <span id="modal-vehiculo-info"></span></p>
                 <p class="mb-0"><strong>KM Actual:</strong> <span id="modal-vehiculo-km"></span></p>
              </div>
            </div>
          </div>

          <!-- Estado y Fechas -->
          <div class="col-12 mb-3">
            <div class="card bg-lighter border-0">
              <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                         <p class="mb-1"><strong>Estado Actual:</strong> <span id="modal-etapa" class="badge bg-label-primary"></span></p>
                         <p class="mb-0"><strong>Tipo Orden:</strong> <span id="modal-tipo"></span></p>
                    </div>
                    <div class="col-md-6">
                         <p class="mb-1"><strong>Fecha Ingreso:</strong> <span id="modal-fecha-ingreso"></span></p>
                         <p class="mb-0"><strong>Motivo Ingreso:</strong> <br><span id="modal-motivo"></span></p>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resumen Financiero (si aplica) -->
          <div class="col-12" id="modal-resumen-financiero" style="display:none;">
             <h5 class="mt-2">Resumen Financiero</h5>
             <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Cotizaciones Aprobadas</th>
                            <th>Facturado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="modal-total-cotizado"></td>
                            <td id="modal-total-facturado"></td>
                        </tr>
                    </tbody>
                </table>
             </div>
          </div>

        </div>
        
        <div class="text-center mt-4">
            <a href="#" id="btn-ver-detalle" class="btn btn-primary">Ver Detalle Completo</a>
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
