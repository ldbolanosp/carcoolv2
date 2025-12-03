@extends('layouts/layoutMaster')

@section('title', 'Dashboard Taller')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/app-chat.scss'])
  <style>
    .dashboard-header {
      display: flex;
      margin-bottom: 1rem;
      padding: 0 1rem;
      font-weight: 600;
      color: #5d596c;
      text-transform: uppercase;
      font-size: 0.75rem;
    }

    .col-orden {
      width: 160px;
      min-width: 160px;
      flex: 0 0 auto;
      border-right: 1px solid rgba(75, 70, 92, 0.1);
      margin-right: 0.5rem;
      padding-right: 0.5rem;
    }
    
    @media (min-width: 1400px) {
      .col-orden {
        width: 220px; /* Slightly wider on desktop but fixed */
        min-width: 220px;
        /* Keep border on desktop as requested */
        border-right: 1px solid rgba(75, 70, 92, 0.1);
        margin-right: 1rem;
        padding-right: 1rem;
      }
    }

    .col-stage {
      flex: 1;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 0.5rem;
    }

    .orden-card {
      background: white;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1rem;
      box-shadow: 0 0.25rem 1rem rgba(165, 163, 174, 0.15);
      display: flex;
      align-items: center;
    }

    /* Default stage item styles removed to favor universal compact style */
    
    .stage-box {
      width: 100%;
      height: 60px;
      border-radius: 0.375rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background-color: #f8f9fa;
      color: #dbdade;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .stage-box.active {
      cursor: pointer;
    }

    .stage-box:not(.active):not(.completed) {
      cursor: default;
    }

    .stage-box i {
      font-size: 1.25rem;
      margin-bottom: 0.25rem;
    }

    .stage-box.completed {
      background-color: #e8fbf0;
      color: #28c76f;
    }

    .stage-box.active {
      background-color: #7367f0;
      color: white;
      box-shadow: 0 0.125rem 0.25rem rgba(115, 103, 240, 0.4);
    }

    .stage-box.active:hover {
      background-color: #685dd8;
      box-shadow: 0 0.25rem 0.5rem rgba(115, 103, 240, 0.5);
    }

    .stage-box.active i {
      font-size: 1.5rem;
      margin-bottom: 0;
    }

    .stage-box.active .stage-label {
      font-size: 0.7rem;
      margin-top: 2px;
      font-weight: bold;
    }

    .orden-info h6 {
      margin-bottom: 0.25rem;
      font-weight: 700;
    }

    .orden-info p {
      margin-bottom: 0;
      font-size: 0.85rem;
      color: #6f6b7d;
    }

    .orden-info .text-muted {
      font-size: 0.75rem;
    }

    /* Horizontal scroll container */
    .horizontal-scroll {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      padding-bottom: 0.5rem;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
      /* Vital fix for flexbox overflow */
      min-width: 0;
      width: 100%;
    }

    .horizontal-scroll::-webkit-scrollbar {
      height: 6px;
    }

    .horizontal-scroll::-webkit-scrollbar-track {
      background: rgba(75, 70, 92, 0.05);
      border-radius: 10px;
    }

    .horizontal-scroll::-webkit-scrollbar-thumb {
      background: rgba(75, 70, 92, 0.2);
      border-radius: 10px;
    }

    .horizontal-scroll::-webkit-scrollbar-thumb:hover {
      background: rgba(75, 70, 92, 0.4);
    }

    /* Mobile optimizations - now default for all sizes */
    .stage-item {
      flex: 0 0 100px;
      max-width: 100px;
      padding: 2px;
    }

    .stage-box {
      height: auto;
      min-height: 60px;
      padding: 0.5rem 0.25rem;
    }

    .stage-name {
      display: block; /* Ensure it's visible on all screens */
      font-size: 0.65rem;
      line-height: 1.1;
      margin-bottom: 0.25rem;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      width: 100%;
      padding: 0 4px;
    }
  </style>
@endsection

@section('page-script')
  @vite(['resources/js/dashboard-taller.js'])
@endsection

@section('content')
  <div class="dashboard-taller">

    <!-- Ordenes List -->
    @forelse($ordenes as $orden)
      @php
        $etapaIndex = array_search($orden->etapa_actual, $etapas);
      @endphp

      <div class="orden-card d-flex flex-row align-items-center" data-orden-id="{{ $orden->id }}">
        <!-- Info Column -->
        <div class="col-orden">
          <div class="orden-info">
            <div class="d-flex align-items-center mb-1">
              <h6 class="mb-0 text-truncate">OT-{{ str_pad($orden->id, 3, '0', STR_PAD_LEFT) }}</h6>
              <a href="{{ route('ordenes-trabajo-detalle', $orden->id) }}" class="ms-2 text-body"><i
                  class="ti tabler-link"></i></a>
            </div>
            <p class="text-primary fw-bold text-truncate" title="{{ $orden->cliente->nombre ?? 'Cliente N/A' }}">{{ $orden->cliente->nombre ?? 'Cliente N/A' }}</p>
            <p class="text-muted text-truncate" title="{{ $orden->vehiculo->marca->nombre ?? '' }} {{ $orden->vehiculo->modelo->nombre ?? '' }}">{{ $orden->vehiculo->marca->nombre ?? '' }} {{ $orden->vehiculo->modelo->nombre ?? '' }}
            </p>
            <div class="d-flex gap-3 mt-2">
              <a href="javascript:void(0)"
                class="d-flex align-items-center {{ $orden->adjuntos_count > 0 ? 'text-primary' : 'text-muted' }} trigger-adjuntos"
                data-id="{{ $orden->id }}" title="Archivos Adjuntos">
                <i class="ti tabler-paperclip me-1"></i>
                <small>{{ $orden->adjuntos_count }}</small>
              </a>
              <a href="javascript:void(0)"
                class="d-flex align-items-center {{ $orden->comentarios_count > 0 ? 'text-primary' : 'text-muted' }} trigger-comentarios"
                data-id="{{ $orden->id }}" title="Comentarios">
                <i class="ti tabler-message-dots me-1"></i>
                <small>{{ $orden->comentarios_count }}</small>
              </a>
            </div>
          </div>
        </div>

        <!-- Stages -->
        <div class="horizontal-scroll flex-grow-1 gap-2">

          <!-- Toma de fotografías -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 0 ? 'completed' : ($etapaIndex === 0 ? 'active' : '') }}"
              data-etapa="Toma de fotografías" data-is-active="{{ $etapaIndex === 0 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 0 ? 'true' : 'false' }}">
              <span class="stage-name">FOTOS</span>
              @if ($etapaIndex > 0)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 0)
                <i class="ti tabler-camera"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-camera"></i>
              @endif
            </div>
          </div>

          <!-- Diagnóstico -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 1 ? 'completed' : ($etapaIndex === 1 ? 'active' : '') }}"
              data-etapa="Diagnóstico" data-is-active="{{ $etapaIndex === 1 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 1 ? 'true' : 'false' }}">
              <span class="stage-name">DIAGNÓSTICO</span>
              @if ($etapaIndex > 1)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 1)
                <i class="ti tabler-clipboard-check"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-clipboard-check"></i>
              @endif
            </div>
          </div>

          <!-- Cotizaciones -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 2 ? 'completed' : ($etapaIndex === 2 ? 'active' : '') }}"
              data-etapa="Cotizaciones" data-is-active="{{ $etapaIndex === 2 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 2 ? 'true' : 'false' }}">
              <span class="stage-name">COTIZACIÓN</span>
              @if ($etapaIndex > 2)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 2)
                <i class="ti tabler-file-description"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-file-description"></i>
              @endif
            </div>
          </div>

          <!-- Órdenes de Compra -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 3 ? 'completed' : ($etapaIndex === 3 ? 'active' : '') }}"
              data-etapa="Órdenes de Compra" data-is-active="{{ $etapaIndex === 3 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 3 ? 'true' : 'false' }}">
              <span class="stage-name">COMPRAS</span>
              @if ($etapaIndex > 3)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 3)
                <i class="ti tabler-shopping-cart"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-shopping-cart"></i>
              @endif
            </div>
          </div>

          <!-- Entrega de repuestos -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 4 ? 'completed' : ($etapaIndex === 4 ? 'active' : '') }}"
              data-etapa="Entrega de repuestos" data-is-active="{{ $etapaIndex === 4 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 4 ? 'true' : 'false' }}">
              <span class="stage-name">REPUESTOS</span>
              @if ($etapaIndex > 4)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 4)
                <i class="ti tabler-package"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-package"></i>
              @endif
            </div>
          </div>

          <!-- Ejecución -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 5 ? 'completed' : ($etapaIndex === 5 ? 'active' : '') }}"
              data-etapa="Ejecución" data-is-active="{{ $etapaIndex === 5 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 5 ? 'true' : 'false' }}">
              <span class="stage-name">EJECUCIÓN</span>
              @if ($etapaIndex > 5)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 5)
                <i class="ti tabler-tool"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-tool"></i>
              @endif
            </div>
          </div>

          <!-- Facturación -->
          <div class="stage-item">
            <div
              class="stage-box stage-trigger {{ $etapaIndex > 6 ? 'completed' : ($etapaIndex === 6 ? 'active' : '') }}"
              data-etapa="Facturación" data-is-active="{{ $etapaIndex === 6 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex > 6 ? 'true' : 'false' }}">
              <span class="stage-name">FACTURACIÓN</span>
              @if ($etapaIndex > 6)
                <i class="ti tabler-check"></i>
              @elseif($etapaIndex === 6)
                <i class="ti tabler-file-invoice"></i>
                <span class="stage-label">ACTIVO</span>
              @else
                <i class="ti tabler-file-invoice"></i>
              @endif
            </div>
          </div>

          <!-- Finalizado -->
          <div class="stage-item">
            <div class="stage-box stage-trigger {{ $etapaIndex === 7 ? 'completed' : '' }}" data-etapa="Finalizado"
              data-is-active="{{ $etapaIndex === 7 ? 'true' : 'false' }}"
              data-is-completed="{{ $etapaIndex === 7 ? 'true' : 'false' }}">
              <span class="stage-name">FINALIZADO</span>
              @if ($etapaIndex === 7)
                <i class="ti tabler-check"></i>
                <span class="stage-label">LISTO</span>
              @else
                <i class="ti tabler-check"></i>
              @endif
            </div>
          </div>

        </div>
      </div>
    @empty
      <div class="text-center p-5">
        <h4 class="text-muted">No hay órdenes de trabajo activas</h4>
      </div>
    @endforelse
  </div>

  <!-- Modales (Estructura compartida) -->

  <!-- Modal Subir Fotos (Ahora incluye Galería) -->
  <div class="modal fade" id="modalFotos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Fotografías - <span id="modalFotosOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Dropzone -->
          <div id="dropzone-container" class="mb-4">
            <form action="/" class="dropzone needsclick" id="dropzone-dashboard">
              <div class="dz-message needsclick">
                <i class="icon-base ti tabler-cloud-upload icon-3x mb-3"></i>
                <h5>Arrastra las fotografías aquí o haz clic para seleccionar</h5>
              </div>
              <div class="fallback">
                <input name="file" type="file" multiple />
              </div>
            </form>
          </div>

          <hr>

          <!-- Galería -->
          <h6 class="mb-3">Galería</h6>
          <div class="row g-4" id="fotografias-container">
            <!-- Fotos dinámicas -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success d-none" id="btn-completar-fotos">
            <i class="icon-base ti tabler-check icon-sm me-2"></i>
            Completar Etapa y Avanzar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Diagnóstico -->
  <div class="modal fade" id="modalDiagnostico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Diagnóstico - <span id="modalDiagnosticoOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-diagnostico">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="duracion_diagnostico">Duración (Horas)</label>
                <input type="number" class="form-control" id="duracion_diagnostico" name="duracion_diagnostico"
                  min="0" step="0.5">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label" for="diagnosticado_por">Diagnosticado por</label>
                <select class="form-select select2" id="diagnosticado_por" name="diagnosticado_por">
                  <option value="">Seleccionar técnico</option>
                  <!-- Técnicos dinámicos -->
                </select>
              </div>
              <div class="col-12 mb-3">
                <label class="form-label" for="detalle_diagnostico">Detalle de la revisión</label>
                <textarea class="form-control" id="detalle_diagnostico" name="detalle_diagnostico" rows="6"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="btn-guardar-diagnostico">
            <i class="icon-base ti tabler-device-floppy icon-sm me-1"></i> Guardar
          </button>
          <button type="button" class="btn btn-success" id="btn-completar-diagnostico">
            <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Cotizaciones -->
  <div class="modal fade" id="modalCotizaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cotizaciones - <span id="modalCotizacionesOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4" id="busqueda-cotizacion-container">
            <label class="form-label">Agregar Cotización de Alegra</label>
            <div class="input-group">
              <input type="text" class="form-control" id="numero_cotizacion_alegra"
                placeholder="Número de cotización (Ej: 1234)">
              <button class="btn btn-primary" type="button" id="btn-buscar-cotizacion">
                <i class="icon-base ti tabler-search icon-sm"></i>
              </button>
            </div>
            <div id="resultado-cotizacion" class="mt-3 d-none">
              <div class="alert alert-secondary d-flex justify-content-between align-items-center" role="alert">
                <div>
                  <h6 class="alert-heading mb-1">Cotización encontrada</h6>
                  <p class="mb-0" id="cotizacion-info"></p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="btn-agregar-cotizacion">
                  <i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar
                </button>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Cliente</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>PDF</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tabla-cotizaciones">
                <!-- Filas dinámicas -->
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success" id="btn-completar-cotizaciones">
            <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Órdenes de Compra -->
  <div class="modal fade" id="modalOrdenesCompra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Órdenes de Compra - <span id="modalOrdenesCompraOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4" id="busqueda-oc-container">
            <label class="form-label">Agregar Orden de Compra de Alegra</label>
            <div class="input-group">
              <input type="text" class="form-control" id="numero_orden_compra_alegra"
                placeholder="Número de orden (Ej: 1234)">
              <button class="btn btn-primary" type="button" id="btn-buscar-orden-compra">
                <i class="icon-base ti tabler-search icon-sm"></i>
              </button>
            </div>
            <div id="resultado-orden-compra" class="mt-3 d-none">
              <div class="alert alert-secondary d-flex justify-content-between align-items-center" role="alert">
                <div>
                  <h6 class="alert-heading mb-1">Orden de Compra encontrada</h6>
                  <p class="mb-0" id="orden-compra-info"></p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="btn-agregar-orden-compra">
                  <i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar
                </button>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Proveedor</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>PDF</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tabla-ordenes-compra">
                <!-- Filas dinámicas -->
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success" id="btn-completar-ordenes-compra">
            <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Entrega de Repuestos -->
  <div class="modal fade" id="modalEntregaRepuestos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Entrega de Repuestos - <span id="modalEntregaRepuestosOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-column gap-3">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="check-repuestos-entregados">
                <input class="form-check-input" type="checkbox" id="check-repuestos-entregados">
                <span class="custom-option-header">
                  <span class="h6 mb-0">Confirmar Entrega de Repuestos</span>
                </span>
                <span class="custom-option-body">
                  <small>Marcar esta casilla cuando los repuestos hayan sido entregados físicamente.</small>
                </span>
              </label>
            </div>

            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="check-tiquete-impreso">
                <input class="form-check-input" type="checkbox" id="check-tiquete-impreso" disabled>
                <span class="custom-option-header">
                  <span class="h6 mb-0">Tiquete Impreso</span>
                </span>
                <span class="custom-option-body">
                  <small>Se marcará automáticamente al imprimir el tiquete.</small>
                </span>
              </label>
            </div>

            <div class="text-center mt-3">
              <a href="#" target="_blank" class="btn btn-outline-primary w-100" id="btn-imprimir-tiquete">
                <i class="icon-base ti tabler-printer icon-sm me-1"></i> Imprimir Tiquete de Repuestos
              </a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success" id="btn-completar-entrega-repuestos">
            <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Facturación -->
  <div class="modal fade" id="modalFacturacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Facturación - <span id="modalFacturacionOrden"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4" id="busqueda-factura-container">
            <label class="form-label">Agregar Factura de Alegra</label>
            <div class="input-group">
              <input type="text" class="form-control" id="numero_factura_alegra"
                placeholder="Número de factura (Ej: 1234)">
              <button class="btn btn-primary" type="button" id="btn-buscar-factura">
                <i class="icon-base ti tabler-search icon-sm"></i>
              </button>
            </div>
            <div id="resultado-factura" class="mt-3 d-none">
              <div class="alert alert-secondary d-flex justify-content-between align-items-center" role="alert">
                <div>
                  <h6 class="alert-heading mb-1">Factura encontrada</h6>
                  <p class="mb-0" id="factura-info"></p>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="btn-agregar-factura">
                  <i class="icon-base ti tabler-plus icon-sm me-1"></i> Agregar
                </button>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Cliente</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>PDF</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tabla-facturas">
                <!-- Filas dinámicas -->
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-success" id="btn-completar-facturacion">
            <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Offcanvas Adjuntos -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdjuntos" aria-labelledby="offcanvasAdjuntosLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasAdjuntosLabel" class="offcanvas-title">Archivos Adjuntos</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-sm btn-primary" id="btn-subir-adjunto-offcanvas">
          <i class="ti tabler-upload me-1"></i> Subir Archivo
        </button>
      </div>
      <!-- Dropzone escondido para funcionalidad -->
      <div id="dropzone-adjuntos-offcanvas-container" class="d-none mb-3">
        <form action="/" class="dropzone needsclick" id="dropzone-adjuntos-offcanvas">
          <div class="dz-message needsclick">
            <span class="text-muted">Arrastra archivos aquí</span>
          </div>
          <div class="fallback">
            <input name="file" type="file" multiple />
          </div>
        </form>
      </div>
      <ul class="list-group list-group-flush" id="lista-adjuntos-offcanvas">
        <!-- Lista dinámica -->
      </ul>
    </div>
  </div>

  <!-- Offcanvas Comentarios -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasComentarios"
    aria-labelledby="offcanvasComentariosLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasComentariosLabel" class="offcanvas-title">Comentarios</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
      <div class="app-chat flex-grow-1 overflow-hidden" style="position: relative; height: 100%;">
        <div class="app-chat-history h-100" style="background-color: #ffffff;">
          <div class="chat-history-body h-100 p-4" id="lista-comentarios-offcanvas" style="overflow-y: auto;">
            <!-- Timeline dinámico -->
          </div>
        </div>
      </div>
      <div class="p-3 border-top">
        <div class="d-flex gap-2">
          <textarea class="form-control" id="nuevo-comentario-offcanvas" rows="2"
            placeholder="Escribe un comentario..."></textarea>
          <button type="button" class="btn btn-primary align-self-end" id="btn-enviar-comentario-offcanvas">
            <i class="icon-base ti tabler-send"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para ver imagen en grande -->
  <div class="modal fade" id="modalImagen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalImagenTitulo">Imagen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img id="imagenModal" src="" alt="" class="img-fluid" style="max-height: 70vh;">
        </div>
      </div>
    </div>
  </div>

  <script>
    // Global function for image preview (simpler to keep global)
    function abrirImagen(ruta, nombre) {
      document.getElementById('imagenModal').src = ruta;
      document.getElementById('imagenModal').alt = nombre;
      document.getElementById('modalImagenTitulo').textContent = nombre;
      const modal = new bootstrap.Modal(document.getElementById('modalImagen'));
      modal.show();
    }
  </script>

  <script>
    // Pass route for ticket printing to JS
    window.routeTiqueteBase = "{{ url('ordenes-trabajo') }}";
    window.currentUserId = {{ auth()->id() }};
  </script>
@endsection
