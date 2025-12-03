@php
  $pageConfigs = ['contentLayout' => 'wide'];
@endphp

@extends('layouts/layoutMaster')

@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;
@endphp

@section('title', 'Detalle Orden de Trabajo #' . $orden->id)

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/app-chat.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  <script>
    window.ordenId = "{{ $orden->id }}";
    // Pass permissions to JS from backend variable
    window.userPermissions = @json($userPermissions);
  </script>
  @vite(['resources/js/detalle-orden-trabajo.js'])
@endsection

@section('content')
  <div
    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-start mb-6 row-gap-4">
    <div class="d-flex flex-column justify-content-center">
      <div class="mb-1">
        <span class="h5">Orden de Trabajo #{{ $orden->id }}</span>
        <span class="badge bg-label-{{ $orden->tipo_orden === 'Taller' ? 'primary' : 'info' }} me-1 ms-2">
          {{ $orden->tipo_orden }}
        </span>
        <span class="badge bg-label-warning">
          {{ $orden->etapa_actual }}
        </span>
      </div>
      <p class="mb-0">
        Creada el {{ $orden->created_at->format('d/m/Y') }} a las {{ $orden->created_at->format('H:i') }}
      </p>
      <p class="mb-0">
        Última actualización el {{ $orden->updated_at->format('d/m/Y') }} a las {{ $orden->updated_at->format('H:i') }}
      </p>
    </div>
    <div class="d-flex flex-column justify-content-center">
      <div class="mb-1">
        <h6 class="mb-0 text-heading text-nowrap">{{ $orden->cliente->nombre }}</h6>
        <span>ID: {{ $orden->cliente->numero_identificacion }}</span>
        <p class="mb-1">Email: {{ $orden->cliente->correo_electronico ?? 'N/A' }}</p>
        <p class="mb-0">Teléfono: {{ $orden->cliente->telefono ?? 'N/A' }}</p>
      </div>
    </div>
    <div class="d-flex flex-column justify-content-center">
      <div class="mb-1">
        <h6 class="mb-0 text-heading">{{ $orden->vehiculo->placa }}</h6>
        <span>{{ $orden->vehiculo->marca->nombre ?? '' }} {{ $orden->vehiculo->modelo->nombre ?? '' }}</span>
        <span>{{ $orden->vehiculo->ano }}</span>
        <div class="avatar avatar-xs me-2">
          <span class="avatar-initial rounded-circle" style="background-color: {{ $orden->vehiculo->color }};"></span>
        </div>
      </div>
    </div>

    <div class="d-flex align-content-center flex-wrap gap-2">
      <a href="{{ route('ordenes-trabajo') }}" class="btn btn-label-secondary">
        <i class="icon-base ti tabler-arrow-left icon-sm me-2"></i>
        Volver
      </a>
    </div>
  </div>

  <div class="row">
    <!-- Main Column -->
    <div class="col-12 col-lg-8">

      <!-- Timeline Card -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="card-title m-0">Progreso de la Orden</h5>
        </div>
        <div class="card-body pt-1">
          <ul class="timeline pb-0 mb-0">
            @php
              $etapas = [
                  'Toma de fotografías',
                  'Diagnóstico',
                  'Cotizaciones',
                  'Órdenes de Compra',
                  'Entrega de repuestos',
                  'Ejecución',
                  'Facturación',
                  'Finalizado',
              ];
              $etapaActualIndex = array_search($orden->etapa_actual, $etapas);
              $diagnosticoIndex = array_search('Diagnóstico', $etapas);
            @endphp

            @foreach ($etapas as $index => $etapa)
              @php
                $isCompleted = $index <= $etapaActualIndex;
                $isCurrent = $index === $etapaActualIndex;
                // Last item usually doesn't have a border, or use 'border-transparent'
$borderClass = $index === count($etapas) - 1 ? 'border-transparent pb-0' : 'border-primary';
if (!$isCompleted) {
    $borderClass = $index === count($etapas) - 1 ? 'border-transparent pb-0' : 'border-dashed';
}

$pointClass = $isCompleted ? 'timeline-point-primary' : 'timeline-point-secondary';
if ($isCurrent) {
    $pointClass = 'timeline-point-warning';
                } // Highlight current
              @endphp

              <li class="timeline-item timeline-item-transparent {{ $borderClass }}">
                <span class="timeline-point {{ $pointClass }}"></span>
                <div class="timeline-event {{ $index === count($etapas) - 1 ? 'pb-0' : '' }}">
                  <div class="timeline-header mb-1">
                    <h6 class="mb-0 {{ $isCompleted ? 'text-heading' : 'text-muted' }}">{{ $etapa }}</h6>
                    @if ($isCurrent)
                      <small class="text-warning fw-bold">Etapa Actual</small>
                    @endif
                  </div>

                  @if ($etapa === 'Toma de fotografías' && ($isCurrent || $orden->fotografias->count() > 0))
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManagePhotos'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalSubirFotos">
                          <i class="icon-base ti tabler-upload icon-sm me-1"></i> Subir Fotos
                        </button>
                      @endif
                      <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                        data-bs-target="#modalGaleria">
                        <i class="icon-base ti tabler-photo icon-sm me-1"></i> Ver Galería
                        <span class="badge bg-label-primary ms-1"
                          id="badge-contador-fotos">{{ $orden->fotografias->count() }}</span>
                      </button>
                    </div>
                  @endif

                  @if ($etapa === 'Diagnóstico' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManageDiagnosis'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalDiagnostico">
                          <i class="icon-base ti tabler-stethoscope icon-sm me-1"></i> Realizar Diagnóstico
                        </button>
                      @else
                        <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                          data-bs-target="#modalDiagnostico">
                          <i class="icon-base ti tabler-eye icon-sm me-1"></i> Ver Diagnóstico
                        </button>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Cotizaciones' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManageQuotes'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalCotizaciones">
                          <i class="icon-base ti tabler-file-dollar icon-sm me-1"></i> Gestionar Cotizaciones
                        </button>
                      @else
                        <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                          data-bs-target="#modalCotizaciones">
                          <i class="icon-base ti tabler-file-dollar icon-sm me-1"></i> Ver Cotizaciones
                        </button>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Órdenes de Compra' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManagePurchaseOrders'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalOrdenesCompra">
                          <i class="icon-base ti tabler-shopping-cart icon-sm me-1"></i> Gestionar Órdenes de Compra
                        </button>
                      @else
                        <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                          data-bs-target="#modalOrdenesCompra">
                          <i class="icon-base ti tabler-shopping-cart icon-sm me-1"></i> Ver Órdenes de Compra
                        </button>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Entrega de repuestos' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManageSpareParts'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalEntregaRepuestos">
                          <i class="icon-base ti tabler-package icon-sm me-1"></i> Entrega de Repuestos
                        </button>
                      @else
                        <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                          data-bs-target="#modalEntregaRepuestos">
                          <i class="icon-base ti tabler-package icon-sm me-1"></i> Ver Entrega
                        </button>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Ejecución' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManageExecution'])
                        <button type="button" class="btn btn-sm btn-primary" id="btn-completar-ejecucion-directo">
                          <i class="icon-base ti tabler-tool icon-sm me-1"></i> Completar Ejecución
                        </button>
                      @elseif ($index < $etapaActualIndex)
                        <span class="badge bg-label-success">Etapa Completada</span>
                      @elseif ($isCurrent)
                        {{-- In current stage but no permission --}}
                        <span class="badge bg-label-warning">En Proceso</span>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Facturación' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($isCurrent && $userPermissions['canManageInvoicing'])
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalFacturacion">
                          <i class="icon-base ti tabler-file-invoice icon-sm me-1"></i> Facturación
                        </button>
                      @else
                        <button type="button" class="btn btn-sm btn-label-secondary" data-bs-toggle="modal"
                          data-bs-target="#modalFacturacion">
                          <i class="icon-base ti tabler-file-invoice icon-sm me-1"></i> Ver Factura
                        </button>
                      @endif
                    </div>
                  @endif

                  @if ($etapa === 'Finalizado' && $index <= $etapaActualIndex)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      @if ($orden->estado === 'Cerrada')
                        <span class="badge bg-label-dark me-2">Orden Cerrada</span>
                      @else
                        <span class="badge bg-label-success me-2">Orden Finalizada</span>
                        @if ($userPermissions['canCloseOrder'])
                          <button type="button" class="btn btn-sm btn-primary" id="btn-cerrar-orden">
                            <i class="icon-base ti tabler-check icon-sm me-1"></i> Cerrar Orden
                          </button>
                        @endif
                      @endif
                    </div>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>



      <!-- Archivos Adjuntos Card -->
      <div class="card mb-6" id="adjuntos">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title m-0">Archivos Adjuntos</h5>
          @if ($userPermissions['canCreate'])
            {{-- Assuming create perm allows uploading general attachments or define new perm --}}
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
              data-bs-target="#modalSubirAdjuntos">
              <i class="icon-base ti tabler-upload icon-sm me-1"></i> Subir Archivo
            </button>
          @endif
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush" id="lista-adjuntos">
            @forelse ($orden->adjuntos as $adjunto)
              <li class="list-group-item d-flex justify-content-between align-items-center px-0"
                id="adjunto-{{ $adjunto->id }}">
                <div class="d-flex align-items-center">
                  <i class="icon-base ti tabler-file me-2"></i>
                  <div class="d-flex flex-column">
                    <a href="{{ Storage::url($adjunto->ruta_archivo) }}" target="_blank"
                      class="text-heading fw-medium">{{ $adjunto->nombre_archivo }}</a>
                    <small class="text-muted">{{ $adjunto->created_at->format('d/m/Y H:i') }}</small>
                  </div>
                </div>
                @if ($userPermissions['canCreate'])
                  {{-- Using same perm for delete as upload for now --}}
                  <button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill eliminar-adjunto"
                    data-id="{{ $adjunto->id }}">
                    <i class="icon-base ti tabler-trash"></i>
                  </button>
                @endif
              </li>
            @empty
              <li class="list-group-item px-0 text-center text-muted" id="no-adjuntos">No hay archivos adjuntos</li>
            @endforelse
          </ul>
        </div>
      </div>



    </div>

    <!-- Sidebar Column -->
    <div class="col-12 col-lg-4">
      <!-- General Info Card -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="card-title m-0">Información General</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <h6 class="mb-1">Motivo de Ingreso</h6>
              <p class="text-body">{{ $orden->motivo_ingreso }}</p>
            </div>
            <div class="col-md-6 mb-3">
              <h6 class="mb-1">Kilometraje Actual</h6>
              <p class="text-body">
                {{ $orden->km_actual ? number_format($orden->km_actual, 0, ',', '.') . ' km' : 'No registrado' }}</p>
            </div>

          </div>
        </div>
      </div>
      <!-- Comentarios Card -->
      <div class="card mb-6" id="comentarios">
        <div class="card-header">
          <h5 class="card-title m-0">Comentarios</h5>
        </div>
        <div class="card-body">
          <div class="app-chat" style="block-size: auto; position: static;">
            <div class="app-chat-history" style="block-size: auto; position: static; background-color: #ffffff;">
              <div class="chat-history-body" style="block-size: auto; padding: 0; overflow: visible;">
                <ul class="list-unstyled chat-history mb-0" id="lista-comentarios">
                  @forelse ($orden->comentarios as $comentario)
                    <li class="chat-message chat-message-right" id="comentario-{{ $comentario->id }}"
                      style="margin-block-end: 1rem;">
                      <div class="d-flex overflow-hidden">
                        <div class="chat-message-wrapper flex-grow-1">
                          <div class="chat-message-text">
                            <p class="mb-0">{{ $comentario->comentario }}</p>
                          </div>
                          <div
                            class="{{ $comentario->user_id === auth()->id() ? 'text-end text-muted' : 'text-muted' }} mt-1">
                            @if ($comentario->user_id === auth()->id())
                              <i class="icon-base ti tabler-checks icon-16px text-success me-1"></i>
                            @endif
                            <small>{{ $comentario->usuario->name }} •
                              {{ $comentario->created_at->format('d/m/Y H:i') }}</small>
                            @if ($comentario->user_id === auth()->id())
                              <button type="button"
                                class="btn btn-sm btn-icon btn-text-danger eliminar-comentario ms-1"
                                data-id="{{ $comentario->id }}" title="Eliminar">
                                <i class="icon-base ti tabler-trash icon-sm"></i>
                              </button>
                            @endif
                          </div>
                        </div>
                      </div>
                    </li>
                  @empty
                    <li class="text-center text-muted" id="no-comentarios">No hay comentarios aún</li>
                  @endforelse
                </ul>
              </div>
            </div>
          </div>
          <div class="mt-4">
            <div class="d-flex gap-2">
              <textarea class="form-control" id="nuevo-comentario" rows="2" placeholder="Escribe un comentario..."></textarea>
              <button type="button" class="btn btn-primary align-self-end" id="btn-enviar-comentario">
                <i class="icon-base ti tabler-send"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Modal Subir Fotos -->
  <div class="modal fade" id="modalSubirFotos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Subir Fotografías</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('ordenes-trabajo-subir-fotografia', $orden->id) }}" class="dropzone needsclick"
            id="dropzone-fotografias" enctype="multipart/form-data">
            <div class="dz-message needsclick">
              <i class="icon-base ti tabler-cloud-upload icon-3x mb-3"></i>
              <h5>Arrastra las fotografías aquí o haz clic para seleccionar</h5>
              <span class="note needsclick text-muted">
                Puedes subir múltiples fotografías. Formatos permitidos: JPG, PNG, GIF, WEBP (máx. 10MB cada una)
              </span>
            </div>
            <div class="fallback">
              <input name="file" type="file" multiple />
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Galería -->
  <div class="modal fade" id="modalGaleria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Galería de Fotografías</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4" id="fotografias-container">
            @if ($orden->fotografias->count() > 0)
              @foreach ($orden->fotografias as $foto)
                <div class="col-6 col-md-4 col-lg-3" data-foto-id="{{ $foto->id }}">
                  <div class="card h-100">
                    <div class="card-img-wrapper position-relative">
                      <img src="{{ Storage::url($foto->ruta_archivo) }}" class="card-img-top"
                        alt="{{ $foto->nombre_archivo }}" style="height: 200px; object-fit: cover; cursor: pointer;"
                        onclick="abrirImagen('{{ Storage::url($foto->ruta_archivo) }}', '{{ $foto->nombre_archivo }}')">
                      @if ($orden->etapa_actual === 'Toma de fotografías' && $userPermissions['canManagePhotos'])
                        <button type="button"
                          class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 m-2 eliminar-foto"
                          data-foto-id="{{ $foto->id }}" title="Eliminar">
                          <i class="icon-base ti tabler-trash"></i>
                        </button>
                      @endif
                    </div>
                    <div class="card-body p-3">
                      <p class="card-text small text-muted mb-0" title="{{ $foto->nombre_archivo }}">
                        {{ Str::limit($foto->nombre_archivo, 20) }}
                      </p>
                      <small class="text-muted">{{ $foto->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                  </div>
                </div>
              @endforeach
            @else
              <div class="col-12">
                <div class="text-center py-6">
                  <i class="icon-base ti tabler-photo-off icon-4x text-muted mb-3"></i>
                  <p class="text-muted mb-0">No hay fotografías cargadas aún</p>
                </div>
              </div>
            @endif
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Toma de fotografías' && $userPermissions['canAdvanceStage'])
            <button type="button" class="btn btn-success" id="btn-completar-etapa"
              {{ $orden->fotografias->count() === 0 ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-check icon-sm me-2"></i>
              Completar Etapa y Avanzar a Diagnóstico
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Diagnóstico -->
  <div class="modal fade" id="modalDiagnostico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Diagnóstico del Vehículo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-diagnostico">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="duracion_diagnostico">Duración (Horas)</label>
                <input type="number" class="form-control" id="duracion_diagnostico" name="duracion_diagnostico"
                  value="{{ $orden->duracion_diagnostico }}" min="0" step="0.5"
                  {{ $orden->etapa_actual !== 'Diagnóstico' || !$userPermissions['canManageDiagnosis'] ? 'disabled' : '' }}>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label" for="diagnosticado_por">Diagnosticado por</label>
                <select class="form-select select2" id="diagnosticado_por" name="diagnosticado_por"
                  {{ $orden->etapa_actual !== 'Diagnóstico' || !$userPermissions['canManageDiagnosis'] ? 'disabled' : '' }}>
                  <option value="">Seleccionar técnico</option>
                  @foreach ($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id }}"
                      {{ $orden->diagnosticado_por == $tecnico->id ? 'selected' : '' }}>
                      {{ $tecnico->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 mb-3">
                <label class="form-label" for="detalle_diagnostico">Detalle de la revisión</label>
                <textarea class="form-control" id="detalle_diagnostico" name="detalle_diagnostico" rows="6"
                  {{ $orden->etapa_actual !== 'Diagnóstico' || !$userPermissions['canManageDiagnosis'] ? 'disabled' : '' }}>{{ $orden->detalle_diagnostico }}</textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Diagnóstico' && $userPermissions['canManageDiagnosis'])
            <button type="button" class="btn btn-primary" id="btn-guardar-diagnostico">
              <i class="icon-base ti tabler-device-floppy icon-sm me-1"></i> Guardar
            </button>
            @if ($userPermissions['canAdvanceStage'])
              <button type="button" class="btn btn-success" id="btn-completar-diagnostico"
                {{ !$orden->duracion_diagnostico ? 'disabled' : '' }}>
                <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
              </button>
            @endif
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Cotizaciones -->
  <div class="modal fade" id="modalCotizaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cotizaciones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($orden->etapa_actual === 'Cotizaciones' && $userPermissions['canManageQuotes'])
            <div class="mb-4">
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
          @endif

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
                  @if ($orden->etapa_actual === 'Cotizaciones' && $userPermissions['canManageQuotes'])
                    <th>Acciones</th>
                  @endif
                </tr>
              </thead>
              <tbody id="tabla-cotizaciones">
                @forelse ($orden->cotizaciones as $cotizacion)
                  <tr data-id="{{ $cotizacion->id }}">
                    <td>{{ $cotizacion->numero_cotizacion }}</td>
                    <td>{{ $cotizacion->cliente_nombre }}</td>
                    <td>{{ $cotizacion->fecha_emision }}</td>
                    <td>{{ number_format($cotizacion->total, 2) }}</td>
                    <td>
                      @if ($cotizacion->ruta_pdf)
                        <a href="{{ Storage::url($cotizacion->ruta_pdf) }}" target="_blank"
                          class="btn btn-sm btn-icon btn-label-secondary">
                          <i class="icon-base ti tabler-file-type-pdf"></i>
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if ($cotizacion->aprobada)
                        <span class="badge bg-label-success">Aprobada</span>
                      @else
                        <span class="badge bg-label-secondary">Sin aprobar</span>
                      @endif
                    </td>
                    @if ($orden->etapa_actual === 'Cotizaciones' && $userPermissions['canManageQuotes'])
                      <td>
                        <div class="d-flex gap-2">
                          @if (!$cotizacion->aprobada)
                            <button type="button" class="btn btn-sm btn-icon btn-success aprobar-cotizacion"
                              title="Aprobar" data-id="{{ $cotizacion->id }}">
                              <i class="icon-base ti tabler-check"></i>
                            </button>
                          @endif
                          <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-cotizacion"
                            title="Eliminar" data-id="{{ $cotizacion->id }}">
                            <i class="icon-base ti tabler-trash"></i>
                          </button>
                        </div>
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr id="no-cotizaciones">
                    <td colspan="7" class="text-center text-muted py-4">No hay cotizaciones agregadas</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Cotizaciones' && $userPermissions['canAdvanceStage'])
            <button type="button" class="btn btn-success" id="btn-completar-cotizaciones"
              {{ !$orden->cotizaciones->where('aprobada', true)->count() ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Órdenes de Compra -->
  <div class="modal fade" id="modalOrdenesCompra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Órdenes de Compra</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($orden->etapa_actual === 'Órdenes de Compra' && $userPermissions['canManagePurchaseOrders'])
            <div class="mb-4">
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
          @endif

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Proveedor</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>PDF</th>
                  @if ($orden->etapa_actual === 'Órdenes de Compra' && $userPermissions['canManagePurchaseOrders'])
                    <th>Acciones</th>
                  @endif
                </tr>
              </thead>
              <tbody id="tabla-ordenes-compra">
                @forelse ($orden->ordenesCompra as $ordenCompra)
                  <tr data-id="{{ $ordenCompra->id }}">
                    <td>{{ $ordenCompra->numero_orden }}</td>
                    <td>{{ $ordenCompra->proveedor_nombre }}</td>
                    <td>{{ $ordenCompra->fecha_emision }}</td>
                    <td>{{ number_format($ordenCompra->total, 2) }}</td>
                    <td>
                      @if ($ordenCompra->ruta_pdf)
                        <a href="{{ Storage::url($ordenCompra->ruta_pdf) }}" target="_blank"
                          class="btn btn-sm btn-icon btn-label-secondary">
                          <i class="icon-base ti tabler-file-type-pdf"></i>
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    @if ($orden->etapa_actual === 'Órdenes de Compra' && $userPermissions['canManagePurchaseOrders'])
                      <td>
                        <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-orden-compra"
                          title="Eliminar" data-id="{{ $ordenCompra->id }}">
                          <i class="icon-base ti tabler-trash"></i>
                        </button>
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr id="no-ordenes-compra">
                    <td colspan="6" class="text-center text-muted py-4">No hay órdenes de compra agregadas</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Órdenes de Compra' && $userPermissions['canAdvanceStage'])
            <button type="button" class="btn btn-success" id="btn-completar-ordenes-compra"
              {{ !$orden->ordenesCompra->count() ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Entrega de Repuestos -->
  <div class="modal fade" id="modalEntregaRepuestos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Entrega de Repuestos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-column gap-3">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="check-repuestos-entregados">
                <input class="form-check-input" type="checkbox" id="check-repuestos-entregados"
                  {{ $orden->repuestos_entregados ? 'checked' : '' }}
                  {{ $orden->etapa_actual !== 'Entrega de repuestos' || !$userPermissions['canManageSpareParts'] ? 'disabled' : '' }}>
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
                <input class="form-check-input" type="checkbox" id="check-tiquete-impreso"
                  {{ $orden->tiquete_impreso ? 'checked' : '' }}
                  {{ $orden->etapa_actual !== 'Entrega de repuestos' || !$userPermissions['canManageSpareParts'] ? 'disabled' : '' }}
                  disabled>
                <span class="custom-option-header">
                  <span class="h6 mb-0">Tiquete Impreso</span>
                </span>
                <span class="custom-option-body">
                  <small>Se marcará automáticamente al imprimir el tiquete.</small>
                </span>
              </label>
            </div>

            <div class="text-center mt-3">
              <a href="{{ route('ordenes-trabajo-imprimir-tiquete-repuestos', $orden->id) }}" target="_blank"
                class="btn btn-outline-primary w-100" id="btn-imprimir-tiquete">
                <i class="icon-base ti tabler-printer icon-sm me-1"></i> Imprimir Tiquete de Repuestos
              </a>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Entrega de repuestos' && $userPermissions['canAdvanceStage'])
            <button type="button" class="btn btn-success" id="btn-completar-entrega-repuestos"
              {{ !$orden->repuestos_entregados || !$orden->tiquete_impreso ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Facturación -->
  <div class="modal fade" id="modalFacturacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Facturación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($orden->etapa_actual === 'Facturación' && $userPermissions['canManageInvoicing'])
            <div class="mb-4">
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
          @endif

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Número</th>
                  <th>Cliente</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>PDF</th>
                  @if ($orden->etapa_actual === 'Facturación' && $userPermissions['canManageInvoicing'])
                    <th>Acciones</th>
                  @endif
                </tr>
              </thead>
              <tbody id="tabla-facturas">
                @forelse ($orden->facturas as $factura)
                  <tr data-id="{{ $factura->id }}">
                    <td>{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->cliente_nombre }}</td>
                    <td>{{ $factura->fecha_emision }}</td>
                    <td>{{ number_format($factura->total, 2) }}</td>
                    <td>
                      @if ($factura->ruta_pdf)
                        <a href="{{ Storage::url($factura->ruta_pdf) }}" target="_blank"
                          class="btn btn-sm btn-icon btn-label-secondary">
                          <i class="icon-base ti tabler-file-type-pdf"></i>
                        </a>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    @if ($orden->etapa_actual === 'Facturación' && $userPermissions['canManageInvoicing'])
                      <td>
                        <button type="button" class="btn btn-sm btn-icon btn-danger eliminar-factura" title="Eliminar"
                          data-id="{{ $factura->id }}">
                          <i class="icon-base ti tabler-trash"></i>
                        </button>
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr id="no-facturas">
                    <td colspan="6" class="text-center text-muted py-4">No hay facturas agregadas</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
          @if ($orden->etapa_actual === 'Facturación' && $userPermissions['canAdvanceStage'])
            <button type="button" class="btn btn-success" id="btn-completar-facturacion"
              {{ !$orden->facturas->count() ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-check icon-sm me-2"></i> Completar Etapa
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Subir Adjuntos -->
  <div class="modal fade" id="modalSubirAdjuntos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Subir Archivos Adjuntos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('ordenes-trabajo-subir-adjunto', $orden->id) }}" class="dropzone needsclick"
            id="dropzone-adjuntos" enctype="multipart/form-data">
            <div class="dz-message needsclick">
              <i class="icon-base ti tabler-file-upload icon-3x mb-3"></i>
              <h5>Arrastra los archivos aquí o haz clic para seleccionar</h5>
              <span class="note needsclick text-muted">
                Puedes subir múltiples archivos. (máx. 20MB cada uno)
              </span>
            </div>
            <div class="fallback">
              <input name="file" type="file" multiple />
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
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
    function abrirImagen(ruta, nombre) {
      document.getElementById('imagenModal').src = ruta;
      document.getElementById('imagenModal').alt = nombre;
      document.getElementById('modalImagenTitulo').textContent = nombre;
      const modal = new bootstrap.Modal(document.getElementById('modalImagen'));
      modal.show();
    }
  </script>
@endsection
