<?php

namespace App\Http\Controllers\configuracion;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $roles = Role::all();
        return view('content.configuracion.usuarios', compact('roles'));
    }

    /**
     * Display a listing of the resource for DataTables.
     */
    public function list(Request $request): JsonResponse
    {
        $columns = [
            1 => 'id',
            2 => 'name',
            3 => 'email',
            4 => 'role', // role column
            5 => 'email_verified_at',
        ];

        $totalData = User::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = User::with('roles'); // Eager load roles

        // Search handling
        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');

            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });

            $totalFiltered = $query->count();
        }

        $users = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];
        $ids = $start;

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->id,
                'fake_id' => ++$ids,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->implode(', '),
                'email_verified_at' => $user->email_verified_at,
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
        $userID = $request->id;

        if ($userID) {
            // update the value
            $user = User::updateOrCreate(
                ['id' => $userID],
                ['name' => $request->name, 'email' => $request->email]
            );

            if ($request->has('role')) {
                 $user->syncRoles($request->role);
            }

            // user updated
            return response()->json('Updated');
        } else {
            // create new one if email is unique
            $userEmail = User::where('email', $request->email)->first();

            if (empty($userEmail)) {
                $user = User::updateOrCreate(
                    ['id' => $userID],
                    ['name' => $request->name, 'email' => $request->email, 'password' => bcrypt(Str::random(10))]
                );

                if ($request->has('role')) {
                    $user->assignRole($request->role);
                }

                // user created
                return response()->json('Created');
            } else {
                // user already exist
                return response()->json(['message' => "already exits"], 422);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $user = User::where('id', $id)->delete();
        return response()->json('Deleted');
    }
}
