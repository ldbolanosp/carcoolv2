@php
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Espacios del Taller')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/notyf/notyf.scss'])
@endsection

@section('page-style')
<style>
  .espacios-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
  }

  @media (min-width: 1400px) {
    .espacios-grid {
      grid-template-columns: repeat(8, 1fr);
    }
  }

  @media (max-width: 1199px) {
    .espacios-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  @media (max-width: 991px) {
    .espacios-grid {
      grid-template-columns: repeat(4, 1fr);
      gap: 0.5rem;
    }
  }

  @media (max-width: 767px) {
    .espacios-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 575px) {
    .espacios-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .espacio-card {
    min-height: 100px;
    border: 2px dashed var(--bs-border-color);
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    position: relative;
    font-size: 0.8rem;
  }

  .espacio-card.ocupado {
    border: 2px solid var(--bs-primary);
    background: var(--bs-body-bg);
    cursor: grab;
  }

  .espacio-card.ocupado:active {
    cursor: grabbing;
  }

  .espacio-card.disponible {
    background: repeating-linear-gradient(
      45deg,
      transparent,
      transparent 8px,
      rgba(0,0,0,0.015) 8px,
      rgba(0,0,0,0.015) 16px
    );
  }

  .espacio-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0.125rem 0.5rem rgba(161, 172, 184, 0.35);
  }

  .espacio-header {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #5f61e6 100%);
    color: white;
    padding: 0.35rem 0.5rem;
    border-radius: 0.25rem 0.25rem 0 0;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .espacio-header.disponible-header {
    background: linear-gradient(135deg, #a8aaae 0%, #8e9095 100%);
  }

  .espacio-body {
    padding: 0.5rem;
  }

  .espacio-numero {
    font-size: 0.85rem;
    font-weight: bold;
  }

  .vehiculo-color-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    border: 1px solid rgba(0,0,0,0.2);
  }

  .etapa-badge {
    font-size: 0.6rem;
    padding: 0.15rem 0.35rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
  }

  .sortable-ghost {
    opacity: 0.4;
    background: var(--bs-primary);
    border: 2px dashed var(--bs-primary);
  }

  .sortable-chosen {
    box-shadow: 0 0.25rem 1rem rgba(0,0,0,0.2);
    transform: rotate(1deg);
  }

  .sortable-drag {
    opacity: 1;
  }

  .espacio-vacio-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 65px;
    color: var(--bs-secondary-color);
  }

  .espacio-vacio-content i {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
  }

  .stats-row {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
  }

  .stat-item.ocupados {
    border-color: var(--bs-danger);
    color: var(--bs-danger);
  }

  .stat-item.disponibles {
    border-color: var(--bs-success);
    color: var(--bs-success);
  }

  .stat-item .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
  }

  .stat-item .stat-label {
    font-size: 0.75rem;
    opacity: 0.8;
  }

  .placa-text {
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cliente-text {
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
  }

  .vehiculo-info {
    font-size: 0.65rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
  }

  .orden-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.35rem;
  }

  .btn-ver-detalle {
    padding: 0.15rem 0.35rem;
    font-size: 0.7rem;
  }

  /* Overlay de bloqueo por inactividad */
  .espacios-wrapper {
    position: relative;
  }

  .bloqueo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 0.375rem;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
  }

  .bloqueo-overlay.active {
    opacity: 1;
    visibility: visible;
  }

  .bloqueo-overlay-content {
    text-align: center;
    color: white;
    padding: 1.5rem;
  }

  .bloqueo-overlay-content i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.9;
  }

  .bloqueo-overlay-content h6 {
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 600;
  }

  .bloqueo-overlay-content p {
    color: rgba(255,255,255,0.8);
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }

  .bloqueo-overlay .btn-refrescar-overlay {
    background: white;
    color: var(--bs-primary);
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .bloqueo-overlay .btn-refrescar-overlay:hover {
    transform: scale(1.05);
    box-shadow: 0 0.25rem 1rem rgba(255,255,255,0.3);
  }

  /* Indicador de tiempo restante */
  .tiempo-restante {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    gap: 0.25rem;
  }

  .tiempo-restante.warning {
    color: var(--bs-warning);
  }

  .tiempo-restante.danger {
    color: var(--bs-danger);
    animation: pulse 1s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  /* Deshabilitar drag cuando está bloqueado */
  .espacios-grid.bloqueado .espacio-card.ocupado {
    cursor: not-allowed;
  }
</style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sortablejs/sortable.js', 'resources/assets/vendor/libs/notyf/notyf.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/js/taller-espacios.js'])
@endsection

@section('content')
  <!-- Header compacto -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div class="d-flex align-items-center gap-3">
      <h5 class="mb-0">
        <i class="icon-base ti tabler-building-warehouse me-2"></i>Espacios del Taller
      </h5>
      <small class="text-muted d-none d-sm-inline">Arrastra para cambiar de espacio</small>
    </div>
    <div class="d-flex align-items-center gap-3">
      <!-- Stats inline -->
      <div class="stats-row">
        <div class="stat-item ocupados">
          <span class="stat-value" id="stat-ocupados">{{ collect($espacios)->where('ocupado', true)->count() }}</span>
          <span class="stat-label">ocupados</span>
        </div>
        <div class="stat-item disponibles">
          <span class="stat-value" id="stat-disponibles">{{ collect($espacios)->where('ocupado', false)->count() }}</span>
          <span class="stat-label">libres</span>
        </div>
      </div>
      <div class="tiempo-restante" id="tiempo-restante">
        <i class="icon-base ti tabler-clock"></i>
        <span id="tiempo-texto">15s</span>
      </div>
      <button type="button" class="btn btn-sm btn-label-secondary" id="btn-refrescar">
        <i class="icon-base ti tabler-refresh"></i>
      </button>
    </div>
  </div>

  <!-- Workshop Spaces Grid -->
  <div class="card">
    <div class="card-body py-3">
      <div class="espacios-wrapper">
        <!-- Overlay de bloqueo -->
        <div class="bloqueo-overlay" id="bloqueo-overlay">
          <div class="bloqueo-overlay-content">
            <i class="icon-base ti tabler-clock-pause"></i>
            <h6>Sesión pausada</h6>
            <p>Los datos pueden estar desactualizados.<br>Refresca para continuar.</p>
            <button type="button" class="btn-refrescar-overlay" id="btn-refrescar-overlay">
              <i class="icon-base ti tabler-refresh me-2"></i>Refrescar
            </button>
          </div>
        </div>
        <div class="espacios-grid" id="espacios-container">
        @foreach($espacios as $numero => $espacio)
          <div class="espacio-card {{ $espacio['ocupado'] ? 'ocupado' : 'disponible' }}"
               data-espacio="{{ $numero }}"
               id="espacio-{{ $numero }}">
            <div class="espacio-header {{ $espacio['ocupado'] ? '' : 'disponible-header' }}">
              <span class="espacio-numero">E{{ $numero }}</span>
              @if($espacio['ocupado'])
                <span class="badge bg-white text-primary" style="font-size: 0.65rem; padding: 0.1rem 0.3rem;">
                  #{{ $espacio['orden']->id }}
                </span>
              @endif
            </div>
            <div class="espacio-body">
              @if($espacio['ocupado'])
                <div class="orden-content" data-orden-id="{{ $espacio['orden']->id }}">
                  <div class="d-flex align-items-center gap-1 mb-1">
                    <span class="vehiculo-color-indicator" style="background-color: {{ $espacio['orden']->vehiculo->color ?? '#cccccc' }};"></span>
                    <span class="placa-text">{{ $espacio['orden']->vehiculo->placa ?? 'N/A' }}</span>
                  </div>
                  <p class="vehiculo-info">
                    {{ Str::limit(($espacio['orden']->vehiculo->marca->nombre ?? '') . ' ' . ($espacio['orden']->vehiculo->modelo->nombre ?? ''), 18) }}
                  </p>
                  <p class="cliente-text">
                    {{ Str::limit($espacio['orden']->cliente->nombre ?? 'N/A', 15) }}
                  </p>
                  <div class="orden-actions">
                    <span class="badge bg-label-warning etapa-badge">
                      {{ Str::limit($espacio['orden']->etapa_actual, 12) }}
                    </span>
                    <a href="{{ route('ordenes-trabajo-detalle', $espacio['orden']->id) }}"
                       class="btn btn-xs btn-text-primary btn-ver-detalle"
                       title="Ver detalle">
                      <i class="icon-base ti tabler-external-link" style="font-size: 0.75rem;"></i>
                    </a>
                  </div>
                </div>
              @else
                <div class="espacio-vacio-content">
                  <i class="icon-base ti tabler-car-off opacity-25"></i>
                  <span style="font-size: 0.65rem;">Libre</span>
                </div>
              @endif
            </div>
          </div>
        @endforeach
        </div>
      </div>
    </div>
  </div>
@endsection
