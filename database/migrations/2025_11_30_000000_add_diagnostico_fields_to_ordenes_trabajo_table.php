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
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            $table->decimal('duracion_diagnostico', 8, 2)->nullable(); // Hours, allowing decimals
            $table->foreignId('diagnosticado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('detalle_diagnostico')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            $table->dropForeign(['diagnosticado_por']);
            $table->dropColumn(['duracion_diagnostico', 'diagnosticado_por', 'detalle_diagnostico']);
        });
    }
};
