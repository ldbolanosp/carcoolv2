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
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_orden', ['Taller', 'Domicilio'])->default('Taller');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('restrict');
            $table->text('motivo_ingreso');
            $table->integer('km_actual')->nullable();
            $table->enum('etapa_actual', [
                'Toma de fotografías',
                'Diagnóstico',
                'Cotizaciones',
                'Órdenes de Compra',
                'Entrega de repuestos',
                'Ejecución',
                'Facturación',
                'Finalizado'
            ])->default('Toma de fotografías');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
