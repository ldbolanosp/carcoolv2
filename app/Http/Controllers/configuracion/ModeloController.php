<?php

namespace App\Http\Controllers\configuracion;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ModeloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $marcas = Marca::where('activo', true)->orderBy('nombre')->get();
        return view('content.configuracion.modelos', compact('marcas'));
    }

    /**
     * Display a listing of the resource for DataTables.
     */
    public function list(Request $request): JsonResponse
    {
        $columns = [
            1 => 'id',
            2 => 'marca_id',
            3 => 'nombre',
            4 => 'activo',
        ];

        $totalData = Modelo::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = Modelo::with('marca');

        // Search handling
        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhereHas('marca', function ($q) use ($search) {
                      $q->where('nombre', 'LIKE', "%{$search}%");
                  });
            });

            $totalFiltered = $query->count();
        }

        $modelos = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        $ids = $start;

        foreach ($modelos as $modelo) {
            $data[] = [
                'id' => $modelo->id,
                'fake_id' => ++$ids,
                'marca_id' => $modelo->marca_id,
                'marca_nombre' => $modelo->marca->nombre ?? '',
                'nombre' => $modelo->nombre,
                'activo' => $modelo->activo,
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
        $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ]);

        $modeloId = $request->id;

        $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activo === null) {
            $activo = true; // default value
        }

        if ($modeloId) {
            // Update existing modelo
            $modelo = Modelo::updateOrCreate(
                ['id' => $modeloId],
                [
                    'marca_id' => $request->marca_id,
                    'nombre' => $request->nombre,
                    'activo' => $activo,
                ]
            );

            return response()->json('Updated');
        } else {
            // Create new modelo
            $modeloExistente = Modelo::where('marca_id', $request->marca_id)
                ->where('nombre', $request->nombre)
                ->first();

            if (empty($modeloExistente)) {
                Modelo::create([
                    'marca_id' => $request->marca_id,
                    'nombre' => $request->nombre,
                    'activo' => $activo,
                ]);

                return response()->json('Created');
            } else {
                return response()->json(['message' => 'El modelo ya existe para esta marca'], 422);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $modelo = Modelo::with('marca')->findOrFail($id);
        return response()->json($modelo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ]);

        $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activo === null) {
            $activo = true; // default value
        }

        $modelo = Modelo::findOrFail($id);
        $modelo->update([
            'marca_id' => $request->marca_id,
            'nombre' => $request->nombre,
            'activo' => $activo,
        ]);

        return response()->json('Updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $modelo = Modelo::findOrFail($id);
        $modelo->delete();

        return response()->json('Deleted');
    }
}
