<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class TallerController extends Controller
{
    /**
     * Display the workshop spaces view.
     */
    public function index(): View
    {
        // Obtener todas las órdenes activas tipo Taller con espacio asignado
        $ordenesEnEspacios = OrdenTrabajo::with(['cliente', 'vehiculo.marca', 'vehiculo.modelo'])
            ->where('tipo_orden', 'Taller')
            ->whereNotNull('espacio_trabajo')
            ->whereNotIn('etapa_actual', ['Cerrada'])
            ->where(function ($q) {
                $q->where('estado', '!=', 'Cerrada')
                    ->orWhereNull('estado');
            })
            ->get()
            ->keyBy('espacio_trabajo');

        // Crear array de espacios con su información
        $espacios = [];
        for ($i = 1; $i <= OrdenTrabajo::TOTAL_ESPACIOS; $i++) {
            $orden = $ordenesEnEspacios->get($i);
            $espacios[$i] = [
                'numero' => $i,
                'ocupado' => $orden !== null,
                'orden' => $orden,
            ];
        }

        return view('content.taller.espacios', compact('espacios'));
    }

    /**
     * Get all workshop spaces data as JSON (for refreshing)
     */
    public function getEspacios(): JsonResponse
    {
        $ordenesEnEspacios = OrdenTrabajo::with(['cliente', 'vehiculo.marca', 'vehiculo.modelo'])
            ->where('tipo_orden', 'Taller')
            ->whereNotNull('espacio_trabajo')
            ->whereNotIn('etapa_actual', ['Cerrada'])
            ->where(function ($q) {
                $q->where('estado', '!=', 'Cerrada')
                    ->orWhereNull('estado');
            })
            ->get();

        $espacios = [];
        for ($i = 1; $i <= OrdenTrabajo::TOTAL_ESPACIOS; $i++) {
            $orden = $ordenesEnEspacios->firstWhere('espacio_trabajo', $i);
            $espacios[$i] = [
                'numero' => $i,
                'ocupado' => $orden !== null,
                'orden' => $orden ? [
                    'id' => $orden->id,
                    'cliente_nombre' => $orden->cliente->nombre ?? '',
                    'vehiculo_placa' => $orden->vehiculo->placa ?? '',
                    'vehiculo_marca' => $orden->vehiculo->marca->nombre ?? '',
                    'vehiculo_modelo' => $orden->vehiculo->modelo->nombre ?? '',
                    'vehiculo_color' => $orden->vehiculo->color ?? '#cccccc',
                    'etapa_actual' => $orden->etapa_actual,
                    'motivo_ingreso' => $orden->motivo_ingreso,
                ] : null,
            ];
        }

        return response()->json([
            'espacios' => $espacios,
            'total_espacios' => OrdenTrabajo::TOTAL_ESPACIOS,
            'espacios_ocupados' => $ordenesEnEspacios->count(),
            'espacios_disponibles' => OrdenTrabajo::TOTAL_ESPACIOS - $ordenesEnEspacios->count(),
        ]);
    }

    /**
     * Update workspace assignment for an order (drag and drop)
     * Supports swapping if target space is occupied
     */
    public function actualizarEspacio(Request $request): JsonResponse
    {
        $request->validate([
            'orden_id' => 'required|exists:ordenes_trabajo,id',
            'nuevo_espacio' => 'required|integer|min:1|max:' . OrdenTrabajo::TOTAL_ESPACIOS,
        ]);

        $orden = OrdenTrabajo::findOrFail($request->orden_id);

        // Verificar que la orden sea de tipo Taller
        if ($orden->tipo_orden !== 'Taller') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden mover órdenes de tipo Taller',
            ], 400);
        }

        $espacioAnterior = $orden->espacio_trabajo;

        // Buscar si hay una orden en el espacio de destino
        $ordenEnDestino = OrdenTrabajo::where('espacio_trabajo', $request->nuevo_espacio)
            ->where('tipo_orden', 'Taller')
            ->whereNotIn('etapa_actual', ['Cerrada'])
            ->where(function ($q) {
                $q->where('estado', '!=', 'Cerrada')
                    ->orWhereNull('estado');
            })
            ->where('id', '!=', $orden->id)
            ->first();

        if ($ordenEnDestino) {
            // Realizar intercambio (swap)
            $ordenEnDestino->espacio_trabajo = $espacioAnterior;
            $ordenEnDestino->save();

            $orden->espacio_trabajo = $request->nuevo_espacio;
            $orden->save();

            return response()->json([
                'success' => true,
                'tipo' => 'swap',
                'message' => "Órdenes intercambiadas: #{$orden->id} → E{$request->nuevo_espacio}, #{$ordenEnDestino->id} → E{$espacioAnterior}",
                'orden_id' => $orden->id,
                'orden_intercambiada_id' => $ordenEnDestino->id,
                'espacio_anterior' => $espacioAnterior,
                'nuevo_espacio' => $request->nuevo_espacio,
            ]);
        }

        // Movimiento simple a espacio vacío
        $orden->espacio_trabajo = $request->nuevo_espacio;
        $orden->save();

        return response()->json([
            'success' => true,
            'tipo' => 'mover',
            'message' => "Orden #{$orden->id} movida del espacio {$espacioAnterior} al espacio {$request->nuevo_espacio}",
            'orden_id' => $orden->id,
            'espacio_anterior' => $espacioAnterior,
            'nuevo_espacio' => $request->nuevo_espacio,
        ]);
    }
}
