<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('restrict');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('restrict');
            $table->integer('ano');
            $table->string('color', 7)->default('#000000'); // Hex color
            $table->string('numero_chasis')->nullable()->unique();
            $table->string('numero_unidad')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
