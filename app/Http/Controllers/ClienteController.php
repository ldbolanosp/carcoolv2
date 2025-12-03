<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\AlegraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('content.clientes.clientes');
    }

    /**
     * Display a listing of the resource for DataTables.
     */
    public function list(Request $request): JsonResponse
    {
        $columns = [
            1 => 'id',
            2 => 'tipo_identificacion',
            3 => 'numero_identificacion',
            4 => 'nombre',
            5 => 'correo_electronico',
            6 => 'telefono',
        ];

        $totalData = Cliente::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = Cliente::query();

        // Search handling
        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('tipo_identificacion', 'LIKE', "%{$search}%")
                    ->orWhere('numero_identificacion', 'LIKE', "%{$search}%")
                    ->orWhere('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('correo_electronico', 'LIKE', "%{$search}%")
                    ->orWhere('telefono', 'LIKE', "%{$search}%")
                    ->orWhere('direccion', 'LIKE', "%{$search}%");
            });

            $totalFiltered = $query->count();
        }

        $clientes = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        $ids = $start;

        foreach ($clientes as $cliente) {
            $data[] = [
                'id' => $cliente->id,
                'fake_id' => ++$ids,
                'tipo_identificacion' => $cliente->tipo_identificacion,
                'numero_identificacion' => $cliente->numero_identificacion,
                'nombre' => $cliente->nombre,
                'correo_electronico' => $cliente->correo_electronico,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
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
        $clienteId = $request->id;

        // Build validation rules
        $rules = [
            'tipo_identificacion' => 'required|in:Física,Jurídica,DIMEX,NITE',
            'nombre' => 'required|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
        ];

        // Add unique validation rules based on whether it's an update or create
        if ($clienteId) {
            $rules['numero_identificacion'] = 'required|string|max:50|unique:clientes,numero_identificacion,' . $clienteId;
            if ($request->correo_electronico) {
                $rules['correo_electronico'] = 'nullable|email|max:255|unique:clientes,correo_electronico,' . $clienteId;
            }
        } else {
            $rules['numero_identificacion'] = 'required|string|max:50|unique:clientes,numero_identificacion';
            if ($request->correo_electronico) {
                $rules['correo_electronico'] = 'nullable|email|max:255|unique:clientes,correo_electronico';
            }
        }

        $request->validate($rules);

        if ($clienteId) {
            // Update existing cliente
            $cliente = Cliente::findOrFail($clienteId);
            $cliente->update([
                'tipo_identificacion' => $request->tipo_identificacion,
                'numero_identificacion' => $request->numero_identificacion,
                'nombre' => $request->nombre,
                'correo_electronico' => $request->correo_electronico,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
            ]);

            return response()->json('Updated');
        } else {
            // Create new cliente
            Cliente::create([
                'tipo_identificacion' => $request->tipo_identificacion,
                'numero_identificacion' => $request->numero_identificacion,
                'nombre' => $request->nombre,
                'correo_electronico' => $request->correo_electronico,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
            ]);

            return response()->json('Created');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);
        return response()->json($cliente);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'tipo_identificacion' => 'required|in:Física,Jurídica,DIMEX,NITE',
            'numero_identificacion' => 'required|string|max:50|unique:clientes,numero_identificacion,' . $id,
            'nombre' => 'required|string|max:255',
            'correo_electronico' => 'nullable|email|max:255|unique:clientes,correo_electronico,' . $id,
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
        ]);

        $cliente->update([
            'tipo_identificacion' => $request->tipo_identificacion,
            'numero_identificacion' => $request->numero_identificacion,
            'nombre' => $request->nombre,
            'correo_electronico' => $request->correo_electronico,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return response()->json('Updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json('Deleted');
    }

    /**
     * Buscar cliente en Alegra por número de identificación
     */
    public function buscarEnAlegra(Request $request): JsonResponse
    {
        $request->validate([
            'numero_identificacion' => 'required|string',
        ]);

        $alegraService = new AlegraService();
        $contacto = $alegraService->buscarContactoPorIdentificacion($request->numero_identificacion);

        if ($contacto) {
            $datosMapeados = $alegraService->mapearDatosCliente($contacto);
            return response()->json([
                'success' => true,
                'data' => $datosMapeados,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró ningún contacto con ese número de identificación',
        ], 404);
    }
}
