<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\Request;

class DashboardTallerController extends Controller
{
  public function index()
  {
    // Obtener órdenes activas (no cerradas)
    // Asumimos que 'Cerrada' es el estado final en la columna 'estado'
    // Y 'etapa_actual' nos dice en qué columna va.

    $ordenes = OrdenTrabajo::with(['cliente', 'vehiculo.marca', 'vehiculo.modelo'])
      ->withCount(['adjuntos', 'comentarios'])
      ->where('estado', '!=', 'Cerrada')
      ->orderBy('created_at', 'desc')
      ->get();

    // Definir las etapas para la vista
    $etapas = [
      'Toma de fotografías',
      'Diagnóstico',
      'Cotizaciones',
      'Órdenes de Compra',
      'Entrega de repuestos',
      'Ejecución',
      'Facturación',
      'Finalizado'
    ];

    return view('content.dashboard.dashboard-taller', compact('ordenes', 'etapas'));
  }
}
