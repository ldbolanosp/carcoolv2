<?php

namespace App\Http\Controllers\configuracion;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('content.configuracion.marcas');
    }

    /**
     * Display a listing of the resource for DataTables.
     */
    public function list(Request $request): JsonResponse
    {
        $columns = [
            1 => 'id',
            2 => 'nombre',
            3 => 'activo',
        ];

        $totalData = Marca::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = Marca::query();

        // Search handling
        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%");
            });

            $totalFiltered = $query->count();
        }

        $marcas = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        $ids = $start;

        foreach ($marcas as $marca) {
            $data[] = [
                'id' => $marca->id,
                'fake_id' => ++$ids,
                'nombre' => $marca->nombre,
                'activo' => $marca->activo,
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
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ]);

        $marcaId = $request->id;

        $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activo === null) {
            $activo = true; // default value
        }

        if ($marcaId) {
            // Update existing marca
            $marca = Marca::updateOrCreate(
                ['id' => $marcaId],
                [
                    'nombre' => $request->nombre,
                    'activo' => $activo,
                ]
            );

            return response()->json('Updated');
        } else {
            // Create new marca
            $marcaExistente = Marca::where('nombre', $request->nombre)->first();

            if (empty($marcaExistente)) {
                Marca::create([
                    'nombre' => $request->nombre,
                    'activo' => $activo,
                ]);

                return response()->json('Created');
            } else {
                return response()->json(['message' => 'La marca ya existe'], 422);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $marca = Marca::findOrFail($id);
        return response()->json($marca);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ]);

        $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activo === null) {
            $activo = true; // default value
        }

        $marca = Marca::findOrFail($id);
        $marca->update([
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
        $marca = Marca::findOrFail($id);
        $marca->delete();

        return response()->json('Deleted');
    }
}
