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
        Schema::create('cotizaciones_orden_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->onDelete('cascade');
            $table->string('numero_cotizacion'); // Alegra estimate number
            $table->string('alegra_id'); // Alegra ID
            $table->string('cliente_nombre');
            $table->string('fecha_emision');
            $table->decimal('total', 12, 2);
            $table->string('ruta_pdf')->nullable(); // Path to PDF
            $table->boolean('aprobada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_orden_trabajo');
    }
};
