<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenTrabajo;
use App\Models\Cliente;

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

    $ordenes = $query->get();

    $events = $ordenes->map(function ($orden) {
      return [
        'id' => $orden->id,
        'title' => 'OT #' . $orden->id . ' - ' . ($orden->vehiculo->placa ?? 'S/P'),
        'start' => $orden->created_at->toIso8601String(),
        'url' => route('ordenes-trabajo-detalle', $orden->id),
        'extendedProps' => [
          'vehiculo' => ($orden->vehiculo->placa ?? 'S/P') . ' ' . ($orden->vehiculo->marca->nombre ?? '') . ' ' . ($orden->vehiculo->modelo->nombre ?? ''),
          'etapa' => $orden->etapa_actual
        ],
        // Color coding based on etapa? Maybe later.
        'classNames' => ['event-orden-trabajo']
      ];
    });

    return response()->json($events);
  }
}
