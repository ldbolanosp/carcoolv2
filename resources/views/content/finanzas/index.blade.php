@extends('layouts/layoutMaster')

@section('title', 'Finanzas')

@section('page-style')
<style>
    html {
        scroll-behavior: auto !important;
    }
    .bg-monitor {
        background-color: #f5eb6e;
    }
    /* Tabla de días del mes - Scroll horizontal siempre habilitado */
    .table-responsive.mes-table {
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        max-width: 100%;
    }
    /* Estilos para la tabla de días del mes */
    .table-mes-dias {
        width: 100%;
        table-layout: fixed;
    }
    /* Primera columna (Rubro) - ancho fijo */
    .table-mes-dias th:first-child,
    .table-mes-dias td:first-child {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 100;
        box-shadow: 2px 0 4px rgba(0,0,0,0.1);
    }
    /* Columna de Total - ancho fijo */
    .table-mes-dias th:last-child,
    .table-mes-dias td:last-child {
        width: 120px;
        min-width: 120px;
        max-width: 120px;
        position: sticky;
        right: 0;
        background-color: #fff;
        z-index: 100;
        box-shadow: -2px 0 4px rgba(0,0,0,0.1);
    }
    /* Columnas de días - ancho igual para todas */
    .table-mes-dias th:not(:first-child):not(:last-child),
    .table-mes-dias td:not(:first-child):not(:last-child) {
        width: 110px;
        min-width: 110px;
        max-width: 110px;
        white-space: nowrap;
        z-index: 1;
    }
    /* Asegurar que el header tenga el mismo fondo al hacer scroll */
    .table-mes-dias thead th:first-child {
        background-color: #dee2e6 !important;
        z-index: 101;
    }
    .table-mes-dias thead th:last-child {
        background-color: #dee2e6 !important;
        z-index: 101;
    }
    /* Columnas de días en el header también deben tener z-index bajo */
    .table-mes-dias thead th:not(:first-child):not(:last-child) {
        z-index: 1;
    }
    /* Mantener fondo sólido sin opacidad para columnas sticky */
    .table-mes-dias tbody tr td:first-child,
    .table-mes-dias tbody tr td:last-child {
        background-color: #fff !important;
    }
    /* Para filas alternadas (striped), usar colores sólidos sin opacidad */
    .table-mes-dias.table-striped tbody tr:nth-of-type(even) td:first-child,
    .table-mes-dias.table-striped tbody tr:nth-of-type(even) td:last-child {
        background-color: #f8f9fa !important;
    }
</style>
@endsection

@section('page-script')
<script>
    function cargarDatosFecha(fecha) {
        if (!fecha) return;

        fetch('{{ route('finanzas.get-date-data') }}?fecha=' + fecha)
            .then(r => r.json())
            .then(data => {
                if (data.existe) {
                    // Ya existe → modo edición
                    document.getElementById('modo').value = 'editar';
                    document.getElementById('btnGuardar').textContent = 'Actualizar';
                    // Llenar campos
                    document.getElementById('gastos').value = data.datos['Gastos Fijos'] ?? '';
                    document.getElementById('ventas').value = data.datos['Ventas'] ?? '';
                    document.getElementById('costos').value = data.datos['Costos'] ?? '';
                    document.getElementById('impventas').value = data.datos['Impuestos de Ventas'] ?? '';
                    document.getElementById('impcompras').value = data.datos['Impuestos de Compras'] ?? '';
                    document.getElementById('retenciones').value = data.datos['Retención 5.31'] ?? '';

                    alert('⚠️ Esta fecha ya tiene datos. Estás en modo edición.');
                } else {
                    // No existe → modo nuevo
                    document.getElementById('modo').value = 'nuevo';
                    document.getElementById('btnGuardar').textContent = 'Guardar';

                    // Limpiar campos
                    document.getElementById('gastos').value = '';
                    document.getElementById('ventas').value = '';
                    document.getElementById('costos').value = '';
                    document.getElementById('impventas').value = '';
                    document.getElementById('impcompras').value = '';
                    document.getElementById('retenciones').value = '';
                }
            })
            .catch(err => console.error(err));
    }
</script>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-sm">
        <h5 class="card-title mb-0">Finanzas</h5>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row mt-4">
    <!-- Formulario Ingreso Diario -->
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Ingreso Diario</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('finanzas.store-daily') }}">
                    @csrf
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha:</label>
                        <input class="form-control" type="date" name="fecha" value="{{ date('Y-m-d') }}" required onchange="cargarDatosFecha(this.value)">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Ventas (Subtotal):</label>
                            <input class="form-control" type="number" step="0.01" name="ventas" id="ventas" required>
                            
                            <label class="form-label mt-2">Impuestos de Ventas (13%):</label>
                            <input class="form-control" type="number" step="0.01" name="impventas" id="impventas" required>
                            
                            <label class="form-label mt-2">Retención 5.31:</label>
                            <input class="form-control" type="number" step="0.01" name="retenciones" id="retenciones" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Costos (Subtotal compras):</label>
                            <input class="form-control" type="number" step="0.01" name="costos" id="costos" required>
                            
                            <label class="form-label mt-2">Impuestos de Compras:</label>
                            <input class="form-control" type="number" step="0.01" name="impcompras" id="impcompras" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gastos Fijos:</label>
                            <input class="form-control" type="number" step="0.01" name="gastos" id="gastos" required>
                        </div>
                    </div>
                    <input type="hidden" name="modo" id="modo" value="nuevo">
                    <div class="mt-4 text-end">
                        <button class="btn btn-primary" id="btnGuardar" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resumen Impuestos -->
    <div class="col-md-4 mb-4">
        @if(!empty($resumen_provision))
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark bg-opacity-10">
                <h6 class="mb-0">📊 Resumen Impuesto Mes Actual</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 text-end align-middle">
                    <tbody>
                        <tr>
                            <td><strong>Impuestos de Ventas</strong></td>
                            <td>₡{{ number_format($resumen_provision['Impuestos de Ventas'], 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Retención 5.31</strong></td>
                            <td>₡{{ number_format($resumen_provision['Retención 5.31'], 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Impuestos de Compras</strong></td>
                            <td>₡{{ number_format($resumen_provision['Impuestos de Compras'], 2) }}</td>
                        </tr>
                        @php
                            $es_negativo = $resumen_provision['Impuesto Neto'] < 0;
                            $es_positivo = $resumen_provision['Impuesto Neto'] > 0;
                            $color = $es_negativo ? 'text-danger' : ($es_positivo ? 'text-success' : 'text-muted');
                            $estado = $es_negativo ? 'a pagar' : ($es_positivo ? 'a favor' : 'sin movimiento');
                        @endphp
                        <tr class="fw-bold">
                            <td>Impuesto Neto</td>
                            <td class="{{ $color }}">
                                ₡{{ number_format($resumen_provision['Impuesto Neto'], 2) }}
                                <small>({{ $estado }})</small>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Impuesto Provisionado</strong></td>
                            <td>₡{{ number_format($resumen_provision['Impuesto Provisionado'], 2) }}</td>
                        </tr>
                        @if($resumen_provision['Pendiente de Provisión'] != 0)
                            <tr class="{{ $resumen_provision['Impuesto Neto'] < 0 ? 'table-warning' : 'table-info' }} fw-bold">
                                <td>{{ $resumen_provision['Impuesto Neto'] < 0 ? 'Pendiente de Provisión' : 'Saldo a Favor (Incluye Provisión)' }}</td>
                                <td>
                                    ₡{{ number_format($resumen_provision['Pendiente de Provisión'], 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>
                @php
                $meses_espanol = array(
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                );
                $mes_formateado = str_pad($mes, 2, '0', STR_PAD_LEFT);
                $nombre_mes = isset($meses_espanol[$mes_formateado]) ? $meses_espanol[$mes_formateado] : $mes;
                @endphp
                📆 {{ $nombre_mes }} {{ $anio }}
            </h5>
            <div>
                <form method="get" action="{{ route('finanzas.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label">Seleccionar mes:</label>
                            <input type="month" name="mes" class="form-control"
                                value="{{ $anio }}-{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Ver</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla Operativa -->
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive mes-table">
                    @php
                    $cantidad_dias = count($dias_mes);
                    $ancho_minimo = 150 + (110 * $cantidad_dias) + 120;
                    @endphp
                    <table class="table table-bordered table-striped text-center align-middle table-mes-dias mb-0" style="min-width: {{ $ancho_minimo }}px;">
                        <thead class="table-secondary">
                            <tr>
                                <th>Rubro</th>
                                @foreach($dias_mes as $d)
                                    <th>{{ date('d', strtotime($d)) }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $rubros = ['Ventas', 'Costos', 'Gastos Fijos'];
                            @endphp
                            @foreach($rubros as $rubro)
                                @php $total_mes = 0; @endphp
                                <tr>
                                    <td><strong>{{ $rubro }}</strong></td>
                                    @foreach($dias_mes as $d)
                                        @php
                                        $valor = isset($datos_mes[$rubro][$d]) ? $datos_mes[$rubro][$d] : 0;
                                        $total_mes += $valor;
                                        @endphp
                                        <td class="text-end">{{ $valor != 0 ? '₡' . number_format($valor, 2) : '-' }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold">{{ $total_mes != 0 ? '₡' . number_format($total_mes, 2) : '-' }}</td>
                                </tr>
                            @endforeach

                            <!-- Utilidad -->
                            @php
                            $ventas_tot = 0; $costos_tot = 0; $gastos_tot = 0;
                            foreach($dias_mes as $d) {
                                $ventas_tot += isset($datos_mes['Ventas'][$d]) ? $datos_mes['Ventas'][$d] : 0;
                                $costos_tot += isset($datos_mes['Costos'][$d]) ? $datos_mes['Costos'][$d] : 0;
                                $gastos_tot += isset($datos_mes['Gastos Fijos'][$d]) ? $datos_mes['Gastos Fijos'][$d] : 0;
                            }
                            $utilidad = $ventas_tot - $costos_tot;
                            $utilidad_neta = $utilidad - $gastos_tot;
                            @endphp
                            
                            <tr class="table-warning fw-bold">
                                <td>Utilidad</td>
                                @foreach($dias_mes as $d)
                                    @php
                                    $v = isset($datos_mes['Ventas'][$d]) ? $datos_mes['Ventas'][$d] : 0;
                                    $c = isset($datos_mes['Costos'][$d]) ? $datos_mes['Costos'][$d] : 0;
                                    $res = $v - $c;
                                    @endphp
                                    <td class="text-end">{{ $res != 0 ? '₡' . number_format($res, 2) : '-' }}</td>
                                @endforeach
                                <td class="text-end">₡{{ number_format($utilidad, 2) }}</td>
                            </tr>

                            <tr class="table-success fw-bold">
                                <td>Utilidad neta</td>
                                @foreach($dias_mes as $d)
                                    @php
                                    $v = isset($datos_mes['Ventas'][$d]) ? $datos_mes['Ventas'][$d] : 0;
                                    $c = isset($datos_mes['Costos'][$d]) ? $datos_mes['Costos'][$d] : 0;
                                    $g = isset($datos_mes['Gastos Fijos'][$d]) ? $datos_mes['Gastos Fijos'][$d] : 0;
                                    $res = $v - $c - $g;
                                    @endphp
                                    <td class="text-end">{{ $res != 0 ? '₡' . number_format($res, 2) : '-' }}</td>
                                @endforeach
                                <td class="text-end">₡{{ number_format($utilidad_neta, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabla Fiscal / Impuestos -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive mes-table">
                    <table class="table table-bordered table-striped text-center align-middle table-mes-dias mb-0" style="min-width: {{ $ancho_minimo }}px;">
                        <thead class="table-secondary">
                            <tr>
                                <th>Rubro</th>
                                @foreach($dias_mes as $d)
                                    <th>{{ date('d', strtotime($d)) }}</th>
                                @endforeach
                                <th class="text-end">Totales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $rubrosImpuestos = ['Impuestos de Ventas', 'Impuestos de Compras', 'Retención 5.31'];
                            @endphp
                            @foreach($rubrosImpuestos as $rubro)
                                <tr>
                                    <td><strong>{{ $rubro }}</strong></td>
                                    @php $total_mes = 0; @endphp
                                    @foreach($dias_mes as $d)
                                        @php
                                        $valor = isset($datos_mes[$rubro][$d]) ? $datos_mes[$rubro][$d] : 0;
                                        $total_mes += $valor;
                                        @endphp
                                        <td class="text-end">{{ $valor != 0 ? '₡' . number_format($valor, 2) : '-' }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold">{{ $total_mes != 0 ? '₡' . number_format($total_mes, 2) : '-' }}</td>
                                </tr>
                            @endforeach

                            <!-- Pago Provisión -->
                            <tr style="background-color:#fff3e0;font-weight:bold;">
                                @php $total_mes_provision = 0; @endphp
                                <td>Pago Provisión</td>
                                @foreach($dias_mes as $d)
                                    @php
                                    $impventas = isset($datos_mes['Impuestos de Ventas'][$d]) ? $datos_mes['Impuestos de Ventas'][$d] : 0;
                                    $impcocompras = isset($datos_mes['Impuestos de Compras'][$d]) ? $datos_mes['Impuestos de Compras'][$d] : 0;
                                    $retenciones = isset($datos_mes['Retención 5.31'][$d]) ? $datos_mes['Retención 5.31'][$d] : 0;
                                    $resultado = $impcocompras + $retenciones - $impventas;
                                    $total_mes_provision += $resultado;
                                    @endphp
                                    <td class="text-end">{{ $resultado != 0 ? '₡' . number_format($resultado, 2) : '-' }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ $total_mes_provision != 0 ? '₡' . number_format($total_mes_provision, 2) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Formulario Provisión -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-bold">
                        Registrar Provisión de Impuesto
                    </div>
                    <div class="card-body">
                        <form id="form-provision" method="post" action="{{ route('finanzas.store-provision') }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Monto (₡)</label>
                                    <input type="number" step="0.01" name="monto" class="form-control" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-warning w-100">Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Historial Provisiones -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-bold">
                        Historial Provisiones ({{ $nombre_mes }})
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Monto (₡)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($provisiones as $p)
                                    <tr>
                                        <td>{{ date('d/m/Y', strtotime($p->date)) }}</td>
                                        <td class="text-end">₡{{ number_format($p->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">Sin registros este mes</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

