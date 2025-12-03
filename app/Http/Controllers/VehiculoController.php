<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Models\Marca;
use App\Models\Modelo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class VehiculoController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(): View
  {
    $marcas = Marca::where('activo', true)->orderBy('nombre')->get();
    return view('content.vehiculos.vehiculos', compact('marcas'));
  }

  /**
   * Get modelos by marca_id
   */
  public function getModelos(Request $request): JsonResponse
  {
    $marcaId = $request->input('marca_id');
    $modelos = Modelo::where('marca_id', $marcaId)
      ->where('activo', true)
      ->orderBy('nombre')
      ->get(['id', 'nombre']);

    return response()->json($modelos);
  }

  /**
   * Display a listing of the resource for DataTables.
   */
  public function list(Request $request): JsonResponse
  {
    $columns = [
      1 => 'id',
      2 => 'placa',
      3 => 'marca_id',
      4 => 'modelo_id',
      5 => 'ano',
      6 => 'color',
    ];

    $totalData = Vehiculo::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')] ?? 'id';
    $dir = $request->input('order.0.dir') ?? 'desc';

    $query = Vehiculo::with(['marca', 'modelo']);

    // Search handling
    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');

      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('placa', 'LIKE', "%{$search}%")
          ->orWhere('ano', 'LIKE', "%{$search}%")
          ->orWhere('numero_chasis', 'LIKE', "%{$search}%")
          ->orWhere('numero_unidad', 'LIKE', "%{$search}%")
          ->orWhereHas('marca', function ($q) use ($search) {
            $q->where('nombre', 'LIKE', "%{$search}%");
          })
          ->orWhereHas('modelo', function ($q) use ($search) {
            $q->where('nombre', 'LIKE', "%{$search}%");
          });
      });

      $totalFiltered = $query->count();
    }

    $vehiculos = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $ids = $start;

    foreach ($vehiculos as $vehiculo) {
      $data[] = [
        'id' => $vehiculo->id,
        'fake_id' => ++$ids,
        'placa' => $vehiculo->placa,
        'marca_id' => $vehiculo->marca_id,
        'marca_nombre' => $vehiculo->marca->nombre ?? '',
        'modelo_id' => $vehiculo->modelo_id,
        'modelo_nombre' => $vehiculo->modelo->nombre ?? '',
        'ano' => $vehiculo->ano,
        'color' => $vehiculo->color,
        'numero_chasis' => $vehiculo->numero_chasis,
        'numero_unidad' => $vehiculo->numero_unidad,
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
    $vehiculoId = $request->id;

    // Build validation rules
    $rules = [
      'marca_id' => 'required|exists:marcas,id',
      'modelo_id' => 'required|exists:modelos,id',
      'ano' => 'required|integer|min:1900|max:' . (date('Y') + 1),
      'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
      'numero_unidad' => 'nullable|string|max:50',
    ];

    // Add unique validation rules based on whether it's an update or create
    if ($vehiculoId) {
      $rules['placa'] = 'required|string|max:20|unique:vehiculos,placa,' . $vehiculoId;
      $rules['numero_chasis'] = 'nullable|string|max:100|unique:vehiculos,numero_chasis,' . $vehiculoId;
    } else {
      $rules['placa'] = 'required|string|max:20|unique:vehiculos,placa';
      $rules['numero_chasis'] = 'nullable|string|max:100|unique:vehiculos,numero_chasis';
    }

    $request->validate($rules);

    if ($vehiculoId) {
      // Update existing vehiculo
      $vehiculo = Vehiculo::findOrFail($vehiculoId);
      $vehiculo->update([
        'placa' => $request->placa,
        'marca_id' => $request->marca_id,
        'modelo_id' => $request->modelo_id,
        'ano' => $request->ano,
        'color' => $request->color,
        'numero_chasis' => $request->numero_chasis,
        'numero_unidad' => $request->numero_unidad,
      ]);

      return response()->json('Updated');
    } else {
      // Create new vehiculo
      Vehiculo::create([
        'placa' => $request->placa,
        'marca_id' => $request->marca_id,
        'modelo_id' => $request->modelo_id,
        'ano' => $request->ano,
        'color' => $request->color,
        'numero_chasis' => $request->numero_chasis,
        'numero_unidad' => $request->numero_unidad,
      ]);

      return response()->json('Created');
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id): JsonResponse
  {
    $vehiculo = Vehiculo::with(['marca', 'modelo'])->findOrFail($id);
    return response()->json($vehiculo);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id): JsonResponse
  {
    $vehiculo = Vehiculo::findOrFail($id);

    $request->validate([
      'placa' => 'required|string|max:20|unique:vehiculos,placa,' . $id,
      'marca_id' => 'required|exists:marcas,id',
      'modelo_id' => 'required|exists:modelos,id',
      'ano' => 'required|integer|min:1900|max:' . (date('Y') + 1),
      'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
      'numero_chasis' => 'nullable|string|max:100|unique:vehiculos,numero_chasis,' . $id,
      'numero_unidad' => 'nullable|string|max:50',
    ]);

    $vehiculo->update([
      'placa' => $request->placa,
      'marca_id' => $request->marca_id,
      'modelo_id' => $request->modelo_id,
      'ano' => $request->ano,
      'color' => $request->color,
      'numero_chasis' => $request->numero_chasis,
      'numero_unidad' => $request->numero_unidad,
    ]);

    return response()->json('Updated');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id): JsonResponse
  {
    $vehiculo = Vehiculo::findOrFail($id);
    $vehiculo->delete();

    return response()->json('Deleted');
  }
}