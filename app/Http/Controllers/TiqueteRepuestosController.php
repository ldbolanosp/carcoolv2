<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\AlegraService;

class TiqueteRepuestosController extends Controller
{
    public function generarPdf($id)
    {
        $orden = OrdenTrabajo::with(['cotizaciones' => function ($query) {
            $query->where('aprobada', true);
        }, 'vehiculo'])->findOrFail($id);

        $cotizacionAprobada = $orden->cotizaciones->first();
        $items = [];

        if ($cotizacionAprobada) {
            $alegraService = new AlegraService();
            $cotizacionDetalle = $alegraService->obtenerCotizacion($cotizacionAprobada->alegra_id);
            
            if ($cotizacionDetalle && isset($cotizacionDetalle['items'])) {
                $items = $cotizacionDetalle['items'];
            }
        }

        $pdf = Pdf::loadView('content.ordenes-trabajo.pdf.tiquete-repuestos', compact('orden', 'items'));
        
        // Set paper size for POS printer (e.g., 80mm width)
        // 80mm is approx 226 points. Height can be long (e.g., 2000) to act as continuous roll
        $pdf->setPaper([0, 0, 226.77, 1000], 'portrait');

        return $pdf->stream('tiquete-repuestos-' . $orden->id . '.pdf');
    }
}
