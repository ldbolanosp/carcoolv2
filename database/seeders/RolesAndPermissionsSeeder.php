<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Órdenes de Trabajo
            'ver_ordenes',
            'crear_ordenes',
            'eliminar_ordenes',
            'gestionar_fotos',
            'gestionar_diagnostico',
            'gestionar_cotizaciones',
            'gestionar_compras',
            'gestionar_repuestos',
            'gestionar_ejecucion',
            'gestionar_facturacion',
            'cerrar_orden',
            
            // Clientes
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'eliminar_clientes',

            // Vehículos
            'ver_vehiculos',
            'crear_vehiculos',
            'editar_vehiculos',
            'eliminar_vehiculos',

            // Configuración
            'gestionar_parametros', // Marcas, Modelos
            'gestionar_usuarios',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }

        // Create Roles (if not exist)
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleRecepcion = Role::firstOrCreate(['name' => 'Recepción']);
        $roleOperaciones = Role::firstOrCreate(['name' => 'Operaciones']);
        $roleTecnico = Role::firstOrCreate(['name' => 'Técnico']);

        // Assign Permissions to Roles
        
        // Administrador: All permissions
        $roleAdmin->givePermissionTo(Permission::all());

        // Recepción
        $roleRecepcion->syncPermissions([
            'ver_ordenes',
            'crear_ordenes',
            'gestionar_fotos', // Can verify/take initial photos
            'gestionar_facturacion',
            'cerrar_orden',
            'ver_clientes',
            'crear_clientes',
            'editar_clientes',
            'ver_vehiculos',
            'crear_vehiculos',
            'editar_vehiculos',
        ]);

        // Operaciones
        $roleOperaciones->syncPermissions([
            'ver_ordenes',
            'gestionar_cotizaciones',
            'gestionar_compras',
            'gestionar_repuestos',
            'ver_clientes',
            'ver_vehiculos',
        ]);

        // Técnico
        $roleTecnico->syncPermissions([
            'ver_ordenes',
            'gestionar_fotos', // Can add photos during diagnosis/execution
            'gestionar_diagnostico',
            'gestionar_ejecucion',
            'ver_clientes',
            'ver_vehiculos',
        ]);
    }
}
