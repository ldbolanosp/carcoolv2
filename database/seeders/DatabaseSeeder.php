<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call(RolesAndPermissionsSeeder::class);
    $this->call(SuperAdminSeeder::class);

    // User::factory(10)->create();

    User::factory()->create([
      'name' => 'Test User',
      'email' => 'test@example.com',
    ]);

    // Migración de datos desde cctallerv3 (descomentar solo cuando se necesite ejecutar)
    // $this->call(MigrateCarcoolDataSeeder::class);
  }
}
