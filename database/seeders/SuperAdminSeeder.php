<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update super admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@carcool.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Administrador role
        $adminRole = Role::where('name', 'Administrador')->first();
        
        if ($adminRole) {
            $superAdmin->assignRole($adminRole);
            $this->command->info('Super Admin created successfully!');
            $this->command->info('Email: admin@carcool.com');
            $this->command->info('Password: admin123');
        } else {
            $this->command->error('Administrador role not found. Please run RolesAndPermissionsSeeder first.');
        }
    }
}
