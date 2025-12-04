<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\FotografiaOrdenTrabajo;
use App\Models\CotizacionOrdenTrabajo;
use App\Models\OrdenCompraOrdenTrabajo;
use App\Models\FacturaOrdenTrabajo;
use App\Models\OrdenTrabajoAdjunto;
use App\Models\OrdenTrabajoComentario;
use App\Models\User;
use App\Services\AlegraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OrdenTrabajoController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display a listing of the resource.
   */
  public function index(): View
  {
    $this->authorize('viewAny', OrdenTrabajo::class);

    $clientes = Cliente::orderBy('nombre')->get();
    $vehiculos = Vehiculo::with(['marca', 'modelo'])->orderBy('placa')->get();
    $espaciosDisponibles = OrdenTrabajo::espaciosDisponibles();

    /** @var User $user */
    $user = Auth::user();

    $userPermissions = [
      'canCreate' => $user->can('crear_ordenes'),
      'canDelete' => $user->can('eliminar_ordenes'),
      // Add more if needed for list view logic
    ];

    return view('content.ordenes-trabajo.ordenes-trabajo', compact('clientes', 'vehiculos', 'userPermissions', 'espaciosDisponibles'));
  }

  /**
   * Get available workspace slots
   */
  public function espaciosDisponibles(Request $request): JsonResponse
  {
    $this->authorize('viewAny', OrdenTrabajo::class);

    $exceptoOrdenId = $request->query('excepto_orden_id');
    $espaciosOcupados = OrdenTrabajo::espaciosOcupados();

    // Si estamos editando una orden, excluir su espacio de los ocupados
    if ($exceptoOrdenId) {
      $orden = OrdenTrabajo::find($exceptoOrdenId);
      if ($orden && $orden->espacio_trabajo) {
        $espaciosOcupados = array_filter($espaciosOcupados, fn($e) => $e != $orden->espacio_trabajo);
      }
    }

    $disponibles = [];
    for ($i = 1; $i <= OrdenTrabajo::TOTAL_ESPACIOS; $i++) {
      if (!in_array($i, $espaciosOcupados)) {
        $disponibles[] = $i;
      }
    }

    return response()->json([
      'espacios_disponibles' => $disponibles,
      'total_espacios' => OrdenTrabajo::TOTAL_ESPACIOS,
      'espacios_ocupados' => count($espaciosOcupados),
    ]);
  }

  /**
   * Display a listing of the resource for DataTables.
   */
  public function list(Request $request): JsonResponse
  {
    $this->authorize('viewAny', OrdenTrabajo::class);

    $columns = [
      1 => 'id',
      2 => 'tipo_orden',
      3 => 'cliente_id',
      4 => 'vehiculo_id',
      5 => 'etapa_actual',
      6 => 'created_at',
    ];

    $totalData = OrdenTrabajo::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')] ?? 'id';
    $dir = $request->input('order.0.dir') ?? 'desc';

    $query = OrdenTrabajo::with(['cliente', 'vehiculo']);

    // Search handling
    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');

      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('tipo_orden', 'LIKE', "%{$search}%")
          ->orWhere('motivo_ingreso', 'LIKE', "%{$search}%")
          ->orWhere('etapa_actual', 'LIKE', "%{$search}%")
          ->orWhereHas('cliente', function ($q) use ($search) {
            $q->where('nombre', 'LIKE', "%{$search}%")
              ->orWhere('numero_identificacion', 'LIKE', "%{$search}%");
          })
          ->orWhereHas('vehiculo', function ($q) use ($search) {
            $q->where('placa', 'LIKE', "%{$search}%");
          });
      });

      $totalFiltered = $query->count();
    }

    $ordenes = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $ids = $start;

    foreach ($ordenes as $orden) {
      $data[] = [
        'id' => $orden->id,
        'fake_id' => ++$ids,
        'tipo_orden' => $orden->tipo_orden,
        'espacio_trabajo' => $orden->espacio_trabajo,
        'cliente_id' => $orden->cliente_id,
        'cliente_nombre' => $orden->cliente->nombre ?? '',
        'vehiculo_id' => $orden->vehiculo_id,
        'vehiculo_placa' => $orden->vehiculo->placa ?? '',
        'vehiculo_info' => ($orden->vehiculo->marca->nombre ?? '') . ' ' . ($orden->vehiculo->modelo->nombre ?? ''),
        'motivo_ingreso' => $orden->motivo_ingreso,
        'km_actual' => $orden->km_actual,
        'etapa_actual' => $orden->etapa_actual,
        'created_at' => $orden->created_at->format('d/m/Y H:i'),
      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'data' => $data,
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request): JsonResponse
  {
    $this->authorize('create', OrdenTrabajo::class);

    $ordenId = $request->id;

    // Build validation rules
    $rules = [
      'tipo_orden' => 'required|in:Taller,Domicilio',
      'cliente_id' => 'required|exists:clientes,id',
      'vehiculo_id' => 'required|exists:vehiculos,id',
      'motivo_ingreso' => 'required|string',
      'km_actual' => 'nullable|integer|min:0',
    ];

    // Si es tipo Taller, el espacio es requerido
    if ($request->tipo_orden === 'Taller') {
      $rules['espacio_trabajo'] = 'required|integer|min:1|max:' . OrdenTrabajo::TOTAL_ESPACIOS;
    }

    $request->validate($rules);

    // Validar que el espacio esté disponible si es tipo Taller
    if ($request->tipo_orden === 'Taller') {
      $espacioDisponible = OrdenTrabajo::espacioDisponible(
        (int) $request->espacio_trabajo,
        $ordenId ? (int) $ordenId : null
      );

      if (!$espacioDisponible) {
        return response()->json([
          'message' => 'El espacio de trabajo seleccionado ya está ocupado',
          'errors' => ['espacio_trabajo' => ['El espacio de trabajo seleccionado ya está ocupado. Por favor, seleccione otro.']]
        ], 422);
      }
    }

    if ($ordenId) {
      // Update existing orden (no se permite cambiar la etapa desde aquí)
      $orden = OrdenTrabajo::findOrFail($ordenId);

      // Use update policy if exists, or fallback to create as general edit permission
      $this->authorize('update', $orden);

      $updateData = [
        'tipo_orden' => $request->tipo_orden,
        'cliente_id' => $request->cliente_id,
        'vehiculo_id' => $request->vehiculo_id,
        'motivo_ingreso' => $request->motivo_ingreso,
        'km_actual' => $request->km_actual,
        // La etapa no se modifica desde el formulario de edición
      ];

      // Manejar espacio de trabajo según el tipo
      if ($request->tipo_orden === 'Taller') {
        $updateData['espacio_trabajo'] = $request->espacio_trabajo;
      } else {
        // Si cambia a Domicilio, liberar el espacio
        $updateData['espacio_trabajo'] = null;
      }

      $orden->update($updateData);

      return response()->json('Updated');
    } else {
      // Create new orden - siempre inicia en "Toma de fotografías"
      $createData = [
        'tipo_orden' => $request->tipo_orden,
        'cliente_id' => $request->cliente_id,
        'vehiculo_id' => $request->vehiculo_id,
        'motivo_ingreso' => $request->motivo_ingreso,
        'km_actual' => $request->km_actual,
        'etapa_actual' => 'Toma de fotografías', // Siempre inicia en esta etapa
      ];

      // Asignar espacio solo si es tipo Taller
      if ($request->tipo_orden === 'Taller') {
        $createData['espacio_trabajo'] = $request->espacio_trabajo;
      }

      OrdenTrabajo::create($createData);

      return response()->json('Created');
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id): JsonResponse
  {
    $orden = OrdenTrabajo::with(['cliente', 'vehiculo.marca', 'vehiculo.modelo'])->findOrFail($id);
    $this->authorize('view', $orden);
    return response()->json($orden);
  }

  /**
   * Get all data needed for modals
   */
  public function getModalData(string $id): JsonResponse
  {
    $orden = OrdenTrabajo::with([
      'cliente',
      'vehiculo.marca',
      'vehiculo.modelo',
      'fotografias',
      'tecnico',
      'cotizaciones',
      'ordenesCompra',
      'facturas',
      'adjuntos',
      'comentarios.usuario'
    ])->findOrFail($id);

    $this->authorize('view', $orden);

    // Transform photographs urls
    $orden->fotografias->transform(function ($foto) {
      $foto->url_completa = Storage::url($foto->ruta_archivo);
      $foto->fecha_formateada = $foto->created_at->format('d/m/Y H:i');
      return $foto;
    });

    // Transform pdf urls for cotizaciones
    $orden->cotizaciones->transform(function ($cotizacion) {
      if ($cotizacion->ruta_pdf) {
        $cotizacion->url_pdf = Storage::url($cotizacion->ruta_pdf);
      }
      return $cotizacion;
    });

    // Transform pdf urls for ordenes compra
    $orden->ordenesCompra->transform(function ($ordenCompra) {
      if ($ordenCompra->ruta_pdf) {
        $ordenCompra->url_pdf = Storage::url($ordenCompra->ruta_pdf);
      }
      return $ordenCompra;
    });

    // Transform pdf urls for facturas
    $orden->facturas->transform(function ($factura) {
      if ($factura->ruta_pdf) {
        $factura->url_pdf = Storage::url($factura->ruta_pdf);
      }
      return $factura;
    });

    // Transform adjuntos urls
    $orden->adjuntos->transform(function ($adjunto) {
      $adjunto->url_completa = Storage::url($adjunto->ruta_archivo);
      $adjunto->fecha_formateada = $adjunto->created_at->format('d/m/Y H:i');
      return $adjunto;
    });

    // Format comments date
    $orden->comentarios->transform(function ($comentario) {
      $comentario->fecha_formateada = $comentario->created_at->format('d/m/Y H:i');
      return $comentario;
    });

    // Get tecnicos
    try {
      $tecnicos = User::role('Tecnico')->get();
      if ($tecnicos->isEmpty()) {
        $tecnicos = User::all();
      }
    } catch (\Exception $e) {
      $tecnicos = User::all();
    }

    return response()->json([
      'orden' => $orden,
      'tecnicos' => $tecnicos
    ]);
  }

  /**
   * Display the detail view.
   */
  public function detalle(string $id): View
  {
    $orden = OrdenTrabajo::with(['cliente', 'vehiculo.marca', 'vehiculo.modelo', 'fotografias', 'tecnico', 'cotizaciones', 'ordenesCompra', 'facturas', 'adjuntos', 'comentarios.usuario'])->findOrFail($id);

    $this->authorize('view', $orden);

    // Get users with role 'Tecnico'. If no role 'Tecnico' exists or no users have it, fallback to all users.
    try {
      $tecnicos = User::role('Tecnico')->get();
      if ($tecnicos->isEmpty()) {
        $tecnicos = User::all();
      }
    } catch (\Exception $e) {
      // If Spatie permission tables are not seeded or role doesn't exist
      $tecnicos = User::all();
    }

    /** @var User $user */
    $user = Auth::user();

    // Pass permissions to view for frontend logic
    $userPermissions = [
      'canCreate' => $user->can('crear_ordenes'),
      'canManagePhotos' => $user->can('gestionar_fotos'),
      'canManageDiagnosis' => $user->can('gestionar_diagnostico'),
      'canManageQuotes' => $user->can('gestionar_cotizaciones'),
      'canManagePurchaseOrders' => $user->can('gestionar_compras'),
      'canManageSpareParts' => $user->can('gestionar_repuestos'),
      'canManageExecution' => $user->can('gestionar_ejecucion'),
      'canManageInvoicing' => $user->can('gestionar_facturacion'),
      'canCloseOrder' => $user->can('cerrar_orden'),
      'canAdvanceStage' => $user->can('advanceStage', $orden),
    ];

    return view('content.ordenes-trabajo.detalle-orden-trabajo', compact('orden', 'tecnicos', 'userPermissions'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('update', $orden);

    $request->validate([
      'tipo_orden' => 'required|in:Taller,Domicilio',
      'cliente_id' => 'required|exists:clientes,id',
      'vehiculo_id' => 'required|exists:vehiculos,id',
      'motivo_ingreso' => 'required|string',
      'km_actual' => 'nullable|integer|min:0',
      // La etapa no se modifica desde el formulario de edición
    ]);

    $orden->update([
      'tipo_orden' => $request->tipo_orden,
      'cliente_id' => $request->cliente_id,
      'vehiculo_id' => $request->vehiculo_id,
      'motivo_ingreso' => $request->motivo_ingreso,
      'km_actual' => $request->km_actual,
      // La etapa se maneja automáticamente en otras partes del sistema
    ]);

    return response()->json('Updated');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('delete', $orden);

    $orden->delete();

    return response()->json('Deleted');
  }

  /**
   * Upload a photograph for an order
   */
  public function subirFotografia(Request $request, string $id): JsonResponse
  {
    try {
      $orden = OrdenTrabajo::findOrFail($id);
      $this->authorize('managePhotos', $orden);

      $request->validate([
        'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // Max 10MB
      ]);

      $file = $request->file('file');
      $nombreOriginal = $file->getClientOriginalName();
      $extension = $file->getClientOriginalExtension();
      $nombreArchivo = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

      // Guardar el archivo
      $rutaArchivo = $file->storeAs('ordenes-trabajo/' . $orden->id, $nombreArchivo, 'public');

      // Guardar en la base de datos
      $fotografia = FotografiaOrdenTrabajo::create([
        'orden_trabajo_id' => $orden->id,
        'ruta_archivo' => $rutaArchivo,
        'nombre_archivo' => $nombreOriginal,
        'tipo_mime' => $file->getMimeType(),
        'tamaño' => $file->getSize(),
      ]);

      return response()->json([
        'success' => true,
        'fotografia' => [
          'id' => $fotografia->id,
          'ruta_archivo' => Storage::url($fotografia->ruta_archivo),
          'nombre_archivo' => $fotografia->nombre_archivo,
        ],
      ])->header('Content-Type', 'application/json');
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error de validación',
        'errors' => $e->errors(),
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al subir la fotografía: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get all photographs for an order
   */
  public function obtenerFotografias(string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('view', $orden); // Viewing photos is part of viewing the order

    $fotografias = $orden->fotografias()->orderBy('created_at', 'desc')->get();

    $fotografiasData = $fotografias->map(function ($foto) {
      return [
        'id' => $foto->id,
        'ruta_archivo' => Storage::url($foto->ruta_archivo),
        'nombre_archivo' => $foto->nombre_archivo,
        'descripcion' => $foto->descripcion,
        'created_at' => $foto->created_at->format('d/m/Y H:i'),
      ];
    });

    return response()->json($fotografiasData);
  }

  /**
   * Delete a photograph
   */
  public function eliminarFotografia(string $ordenId, string $fotografiaId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('managePhotos', $orden);

    $fotografia = FotografiaOrdenTrabajo::where('orden_trabajo_id', $orden->id)
      ->findOrFail($fotografiaId);

    // Eliminar el archivo físico
    if (Storage::disk('public')->exists($fotografia->ruta_archivo)) {
      Storage::disk('public')->delete($fotografia->ruta_archivo);
    }

    // Eliminar el registro
    $fotografia->delete();

    return response()->json(['success' => true]);
  }

  /**
   * Save diagnosis data
   */
  public function guardarDiagnostico(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('manageDiagnosis', $orden);

    $request->validate([
      'duracion_diagnostico' => 'required|numeric|min:0',
      'diagnosticado_por' => 'required|exists:users,id',
      'detalle_diagnostico' => 'required|string',
    ]);

    $orden->update([
      'duracion_diagnostico' => $request->duracion_diagnostico,
      'diagnosticado_por' => $request->diagnosticado_por,
      'detalle_diagnostico' => $request->detalle_diagnostico,
    ]);

    return response()->json(['success' => true, 'message' => 'Diagnóstico guardado exitosamente']);
  }

  /**
   * Search estimate in Alegra
   */
  public function buscarCotizacionAlegra(Request $request): JsonResponse
  {
    // Authorization for searching quotes could be tied to managing quotes
    // Since no Order context is passed yet, we check global permission if possible,
    // but simpler to just allow if they have the permission generally.
    // For now, assuming they are in a context where they CAN manage quotes.
    /** @var User $user */
    $user = Auth::user();
    if (!$user->can('gestionar_cotizaciones')) {
      abort(403);
    }

    $request->validate([
      'numero_cotizacion' => 'required|string',
    ]);

    $alegraService = new AlegraService();
    $cotizacion = $alegraService->buscarCotizacionPorNumero($request->numero_cotizacion);

    if ($cotizacion) {
      return response()->json([
        'success' => true,
        'data' => [
          'id' => $cotizacion['id'],
          'numero' => $cotizacion['numberTemplate']['fullNumber'] ?? $request->numero_cotizacion,
          'fecha' => $cotizacion['date'],
          'cliente' => $cotizacion['client']['name'] ?? 'Desconocido',
          'total' => $cotizacion['total'],
        ],
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'No se encontró ninguna cotización con ese número',
    ], 404);
  }

  /**
   * Add estimate to order
   */
  public function agregarCotizacion(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('manageQuotes', $orden);

    $request->validate([
      'alegra_id' => 'required|string',
      'numero_cotizacion' => 'required|string',
      'cliente_nombre' => 'required|string',
      'fecha_emision' => 'required|string',
      'total' => 'required|numeric',
    ]);

    // Check if already added
    if ($orden->cotizaciones()->where('alegra_id', $request->alegra_id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Esta cotización ya fue agregada a la orden',
      ], 400);
    }

    // Download PDF
    $alegraService = new AlegraService();
    $pdfUrl = $alegraService->obtenerPdfCotizacion($request->alegra_id);
    $rutaPdf = null;

    if ($pdfUrl) {
      // Download and save PDF locally
      try {
        $pdfContent = Http::get($pdfUrl)->body();
        $fileName = 'cotizacion_' . $request->numero_cotizacion . '_' . time() . '.pdf';
        $rutaPdf = 'ordenes-trabajo/' . $orden->id . '/cotizaciones/' . $fileName;
        Storage::disk('public')->put($rutaPdf, $pdfContent);
      } catch (\Exception $e) {
        // Log error but continue saving record
        Log::error('Error downloading PDF: ' . $e->getMessage());
      }
    }

    $cotizacion = CotizacionOrdenTrabajo::create([
      'orden_trabajo_id' => $orden->id,
      'numero_cotizacion' => $request->numero_cotizacion,
      'alegra_id' => $request->alegra_id,
      'cliente_nombre' => $request->cliente_nombre,
      'fecha_emision' => $request->fecha_emision,
      'total' => $request->total,
      'ruta_pdf' => $rutaPdf,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Cotización agregada exitosamente',
      'cotizacion' => $cotizacion
    ]);
  }

  /**
   * Remove estimate from order
   */
  public function eliminarCotizacion(string $ordenId, string $cotizacionId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('manageQuotes', $orden);

    $cotizacion = CotizacionOrdenTrabajo::where('orden_trabajo_id', $orden->id)
      ->findOrFail($cotizacionId);

    // Delete PDF if exists
    if ($cotizacion->ruta_pdf && Storage::disk('public')->exists($cotizacion->ruta_pdf)) {
      Storage::disk('public')->delete($cotizacion->ruta_pdf);
    }

    $cotizacion->delete();

    return response()->json(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
  }

  /**
   * Approve estimate
   */
  public function aprobarCotizacion(string $ordenId, string $cotizacionId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('manageQuotes', $orden);

    $cotizacion = CotizacionOrdenTrabajo::where('orden_trabajo_id', $orden->id)
      ->findOrFail($cotizacionId);

    // Unapprove all others
    $orden->cotizaciones()->update(['aprobada' => false]);

    // Approve this one
    $cotizacion->aprobada = true;
    $cotizacion->save();

    return response()->json(['success' => true, 'message' => 'Cotización aprobada exitosamente']);
  }

  /**
   * Search purchase order in Alegra
   */
  public function buscarOrdenCompraAlegra(Request $request): JsonResponse
  {
    /** @var User $user */
    $user = Auth::user();
    if (!$user->can('gestionar_compras')) {
      abort(403);
    }

    $request->validate([
      'numero_orden' => 'required|string',
    ]);

    $alegraService = new AlegraService();
    $ordenCompra = $alegraService->buscarOrdenCompraPorNumero($request->numero_orden);

    if ($ordenCompra) {
      return response()->json([
        'success' => true,
        'data' => [
          'id' => $ordenCompra['id'],
          'numero' => $ordenCompra['numberTemplate']['fullNumber'] ?? $request->numero_orden,
          'fecha' => $ordenCompra['date'],
          'proveedor' => $ordenCompra['provider']['name'] ?? 'Desconocido',
          'total' => $ordenCompra['total'],
        ],
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'No se encontró ninguna orden de compra con ese número',
    ], 404);
  }

  /**
   * Add purchase order to work order
   */
  public function agregarOrdenCompra(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('managePurchaseOrders', $orden);

    $request->validate([
      'alegra_id' => 'required|string',
      'numero_orden' => 'required|string',
      'proveedor_nombre' => 'required|string',
      'fecha_emision' => 'required|string',
      'total' => 'required|numeric',
    ]);

    // Check if already added
    if ($orden->ordenesCompra()->where('alegra_id', $request->alegra_id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Esta orden de compra ya fue agregada',
      ], 400);
    }

    // Download PDF
    $alegraService = new AlegraService();
    $pdfUrl = $alegraService->obtenerPdfOrdenCompra($request->alegra_id);
    $rutaPdf = null;

    if ($pdfUrl) {
      // Download and save PDF locally
      try {
        $pdfContent = Http::get($pdfUrl)->body();
        $fileName = 'orden_compra_' . $request->numero_orden . '_' . time() . '.pdf';
        $rutaPdf = 'ordenes-trabajo/' . $orden->id . '/ordenes-compra/' . $fileName;
        Storage::disk('public')->put($rutaPdf, $pdfContent);
      } catch (\Exception $e) {
        // Log error but continue saving record
        Log::error('Error downloading PDF: ' . $e->getMessage());
      }
    }

    $ordenCompra = OrdenCompraOrdenTrabajo::create([
      'orden_trabajo_id' => $orden->id,
      'numero_orden' => $request->numero_orden,
      'alegra_id' => $request->alegra_id,
      'proveedor_nombre' => $request->proveedor_nombre,
      'fecha_emision' => $request->fecha_emision,
      'total' => $request->total,
      'ruta_pdf' => $rutaPdf,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Orden de compra agregada exitosamente',
      'orden_compra' => $ordenCompra
    ]);
  }

  /**
   * Remove purchase order from work order
   */
  public function eliminarOrdenCompra(string $ordenId, string $ordenCompraId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('managePurchaseOrders', $orden);

    $ordenCompra = OrdenCompraOrdenTrabajo::where('orden_trabajo_id', $orden->id)
      ->findOrFail($ordenCompraId);

    // Delete PDF if exists
    if ($ordenCompra->ruta_pdf && Storage::disk('public')->exists($ordenCompra->ruta_pdf)) {
      Storage::disk('public')->delete($ordenCompra->ruta_pdf);
    }

    $ordenCompra->delete();

    return response()->json(['success' => true, 'message' => 'Orden de compra eliminada exitosamente']);
  }

  /**
   * Search invoice in Alegra
   */
  public function buscarFacturaAlegra(Request $request): JsonResponse
  {
    /** @var User $user */
    $user = Auth::user();
    if (!$user->can('gestionar_facturacion')) {
      abort(403);
    }

    $request->validate([
      'numero_factura' => 'required|string',
    ]);

    $alegraService = new AlegraService();
    $factura = $alegraService->buscarFacturaPorNumero($request->numero_factura);

    if ($factura) {
      return response()->json([
        'success' => true,
        'data' => [
          'id' => $factura['id'],
          'numero' => $factura['numberTemplate']['fullNumber'] ?? $request->numero_factura,
          'fecha' => $factura['date'],
          'cliente' => $factura['client']['name'] ?? 'Desconocido',
          'total' => $factura['total'],
        ],
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'No se encontró ninguna factura con ese número',
    ], 404);
  }

  /**
   * Add invoice to work order
   */
  public function agregarFactura(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('manageInvoicing', $orden);

    $request->validate([
      'alegra_id' => 'required|string',
      'numero_factura' => 'required|string',
      'cliente_nombre' => 'required|string',
      'fecha_emision' => 'required|string',
      'total' => 'required|numeric',
    ]);

    // Check if already has an invoice (only 1 allowed)
    if ($orden->facturas()->count() > 0) {
      return response()->json([
        'success' => false,
        'message' => 'Solo se permite agregar una factura por orden de trabajo',
      ], 400);
    }

    // Download PDF
    $alegraService = new AlegraService();
    $pdfUrl = $alegraService->obtenerPdfFactura($request->alegra_id);
    $rutaPdf = null;

    if ($pdfUrl) {
      // Download and save PDF locally
      try {
        $pdfContent = Http::get($pdfUrl)->body();
        $fileName = 'factura_' . $request->numero_factura . '_' . time() . '.pdf';
        $rutaPdf = 'ordenes-trabajo/' . $orden->id . '/facturas/' . $fileName;
        Storage::disk('public')->put($rutaPdf, $pdfContent);
      } catch (\Exception $e) {
        // Log error but continue saving record
        Log::error('Error downloading PDF: ' . $e->getMessage());
      }
    }

    $factura = FacturaOrdenTrabajo::create([
      'orden_trabajo_id' => $orden->id,
      'numero_factura' => $request->numero_factura,
      'alegra_id' => $request->alegra_id,
      'cliente_nombre' => $request->cliente_nombre,
      'fecha_emision' => $request->fecha_emision,
      'total' => $request->total,
      'ruta_pdf' => $rutaPdf,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Factura agregada exitosamente',
      'factura' => $factura
    ]);
  }

  /**
   * Remove invoice from work order
   */
  public function eliminarFactura(string $ordenId, string $facturaId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('manageInvoicing', $orden);

    $factura = FacturaOrdenTrabajo::where('orden_trabajo_id', $orden->id)
      ->findOrFail($facturaId);

    // Delete PDF if exists
    if ($factura->ruta_pdf && Storage::disk('public')->exists($factura->ruta_pdf)) {
      Storage::disk('public')->delete($factura->ruta_pdf);
    }

    $factura->delete();

    return response()->json(['success' => true, 'message' => 'Factura eliminada exitosamente']);
  }

  /**
   * Update status for spare parts delivery
   */
  public function actualizarEntregaRepuestos(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('manageSpareParts', $orden);

    $request->validate([
      'repuestos_entregados' => 'required|boolean',
      'tiquete_impreso' => 'required|boolean',
    ]);

    $orden->update([
      'repuestos_entregados' => $request->repuestos_entregados,
      'tiquete_impreso' => $request->tiquete_impreso,
    ]);

    return response()->json(['success' => true, 'message' => 'Estado de entrega actualizado']);
  }

  /**
   * Advance to next stage
   */
  public function avanzarEtapa(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);

    // This will check if user has permission for the CURRENT stage to advance
    $this->authorize('advanceStage', $orden);

    $etapas = [
      'Toma de fotografías',
      'Diagnóstico',
      'Cotizaciones',
      'Órdenes de Compra',
      'Entrega de repuestos',
      'Ejecución',
      'Facturación',
      'Finalizado',
      'Cerrada',
    ];

    $etapaActualIndex = array_search($orden->etapa_actual, $etapas);

    if ($etapaActualIndex === false) {
      return response()->json(['error' => 'Etapa actual no válida'], 400);
    }

    // Validaciones específicas por etapa
    if ($orden->etapa_actual === 'Toma de fotografías') {
      // Verificar que haya al menos una fotografía
      if ($orden->fotografias()->count() === 0) {
        return response()->json([
          'error' => 'Debe cargar al menos una fotografía antes de avanzar a la siguiente etapa',
        ], 400);
      }
    }

    if ($orden->etapa_actual === 'Diagnóstico') {
      if (!$orden->duracion_diagnostico || !$orden->diagnosticado_por || !$orden->detalle_diagnostico) {
        return response()->json([
          'error' => 'Debe completar la información de diagnóstico antes de avanzar',
        ], 400);
      }
    }

    if ($orden->etapa_actual === 'Cotizaciones') {
      if (!$orden->cotizaciones()->where('aprobada', true)->exists()) {
        return response()->json([
          'error' => 'Debe aprobar al menos una cotización para avanzar',
        ], 400);
      }
    }

    if ($orden->etapa_actual === 'Órdenes de Compra') {
      if (!$orden->ordenesCompra()->exists()) {
        return response()->json([
          'error' => 'Debe agregar al menos una orden de compra para avanzar',
        ], 400);
      }
    }

    if ($orden->etapa_actual === 'Entrega de repuestos') {
      if (!$orden->repuestos_entregados || !$orden->tiquete_impreso) {
        return response()->json([
          'error' => 'Debe confirmar la entrega de repuestos e imprimir el tiquete para avanzar',
        ], 400);
      }
    }

    if ($orden->etapa_actual === 'Facturación') {
      if ($orden->facturas()->count() === 0) {
        return response()->json([
          'error' => 'Debe agregar una factura para completar la orden',
        ], 400);
      }
    }

    // Avanzar a la siguiente etapa
    if ($etapaActualIndex < count($etapas) - 1) {
      $orden->etapa_actual = $etapas[$etapaActualIndex + 1];
      $orden->save();

      return response()->json([
        'success' => true,
        'etapa_actual' => $orden->etapa_actual,
        'message' => 'Etapa avanzada exitosamente',
      ]);
    }

    return response()->json(['error' => 'La orden ya está en la etapa final'], 400);
  }

  /**
   * Upload an attachment for an order
   */
  public function subirAdjunto(Request $request, string $id): JsonResponse
  {
    try {
      $orden = OrdenTrabajo::findOrFail($id);
      $this->authorize('view', $orden); // Generally if you can view, you can attach? Or maybe restrict more.

      $request->validate([
        'file' => 'required|file|max:20480', // Max 20MB
      ]);

      $file = $request->file('file');
      $nombreOriginal = $file->getClientOriginalName();
      $extension = $file->getClientOriginalExtension();
      $nombreArchivo = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

      // Guardar el archivo
      $rutaArchivo = $file->storeAs('ordenes-trabajo/' . $orden->id . '/adjuntos', $nombreArchivo, 'public');

      // Guardar en la base de datos
      $adjunto = OrdenTrabajoAdjunto::create([
        'orden_trabajo_id' => $orden->id,
        'ruta_archivo' => $rutaArchivo,
        'nombre_archivo' => $nombreOriginal,
        'tipo_mime' => $file->getMimeType(),
        'tamaño' => $file->getSize(),
      ]);

      return response()->json([
        'success' => true,
        'adjunto' => [
          'id' => $adjunto->id,
          'ruta_archivo' => Storage::url($adjunto->ruta_archivo),
          'nombre_archivo' => $adjunto->nombre_archivo,
          'fecha_formateada' => $adjunto->created_at->format('d/m/Y H:i'),
        ],
      ])->header('Content-Type', 'application/json');
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error de validación',
        'errors' => $e->errors(),
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al subir el archivo: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Delete an attachment
   */
  public function eliminarAdjunto(string $ordenId, string $adjuntoId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $this->authorize('view', $orden); // Should ideally be delete permission or own attachment

    $adjunto = OrdenTrabajoAdjunto::where('orden_trabajo_id', $orden->id)
      ->findOrFail($adjuntoId);

    // Eliminar el archivo físico
    if (Storage::disk('public')->exists($adjunto->ruta_archivo)) {
      Storage::disk('public')->delete($adjunto->ruta_archivo);
    }

    // Eliminar el registro
    $adjunto->delete();

    return response()->json(['success' => true]);
  }

  /**
   * Add a comment
   */
  public function agregarComentario(Request $request, string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('view', $orden); // Viewing allows commenting typically

    $request->validate([
      'comentario' => 'required|string',
    ]);

    $comentario = OrdenTrabajoComentario::create([
      'orden_trabajo_id' => $orden->id,
      'user_id' => Auth::id(),
      'comentario' => $request->comentario,
    ]);

    // Reload relationship to get user info
    $comentario->load('usuario');

    return response()->json([
      'success' => true,
      'comentario' => [
        'id' => $comentario->id,
        'usuario_nombre' => $comentario->usuario->name,
        'usuario_iniciales' => substr($comentario->usuario->name, 0, 2),
        'comentario' => $comentario->comentario,
        'fecha_formateada' => $comentario->created_at->format('d/m/Y H:i'),
        'es_propio' => true,
      ],
    ]);
  }

  /**
   * Delete a comment
   */
  public function eliminarComentario(string $ordenId, string $comentarioId): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($ordenId);
    $comentario = OrdenTrabajoComentario::where('orden_trabajo_id', $orden->id)
      ->findOrFail($comentarioId);

    // Check permissions (only owner or admin could delete, for now just owner)
    if ($comentario->user_id !== Auth::id()) {
      return response()->json(['error' => 'No tienes permiso para eliminar este comentario'], 403);
    }

    $comentario->delete();

    return response()->json(['success' => true]);
  }

  /**
   * Close the work order
   */
  public function cerrarOrden(string $id): JsonResponse
  {
    $orden = OrdenTrabajo::findOrFail($id);
    $this->authorize('closeOrder', $orden);

    if ($orden->etapa_actual !== 'Finalizado') {
      return response()->json(['error' => 'La orden debe estar en la etapa Finalizado para poder cerrarse'], 400);
    }

    // Liberar el espacio de trabajo al cerrar la orden
    $orden->update([
      'estado' => 'Cerrada',
      'etapa_actual' => 'Cerrada', // Also update stage to match new flow logic
      'espacio_trabajo' => null, // Liberar el espacio
    ]);

    return response()->json(['success' => true, 'message' => 'Orden de trabajo cerrada exitosamente']);
  }
}