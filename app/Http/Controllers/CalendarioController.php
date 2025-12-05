<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenTrabajo;
use App\Models\Cliente;
use Carbon\Carbon;

class CalendarioController extends Controller
{
  public function index()
  {
    $clientes = Cliente::select('id', 'nombre')->orderBy('nombre')->get();
    return view('content.calendario.index', compact('clientes'));
  }

  public function getEvents(Request $request)
  {
    $query = OrdenTrabajo::with(['cliente', 'vehiculo']);

    if ($request->has('cliente_id') && $request->cliente_id) {
      $query->where('cliente_id', $request->cliente_id);
    }

    // FullCalendar envía automáticamente 'start' y 'end' en formato ISO8601 (YYYY-MM-DD...)
    if ($request->has('start') && $request->has('end')) {
      $start = Carbon::parse($request->start)->startOfDay();
      $end = Carbon::parse($request->end)->endOfDay();
      $query->whereBetween('created_at', [$start, $end]);
    }

    $ordenes = $query->get();

    $events = $ordenes->map(function ($orden) {
      return [
        'id' => $orden->id,
        'title' => '', // We use content rendering
        'start' => $orden->created_at->toIso8601String(),
        'url' => route('ordenes-trabajo-detalle', $orden->id),
        'extendedProps' => [
          'vehiculo' => ($orden->vehiculo->placa ?? 'S/P'),
          'unidad' => $orden->vehiculo->numero_unidad ?? '',
          'etapa' => $orden->etapa_actual,
          'orden_id' => $orden->id
        ],
        'classNames' => ['event-orden-trabajo']
      ];
    });

    return response()->json($events);
  }
}
