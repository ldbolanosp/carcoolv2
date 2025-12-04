<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'ordenes_trabajo';

    /**
     * Total de espacios de trabajo disponibles en el taller
     */
    public const TOTAL_ESPACIOS = 16;

    protected $fillable = [
        'tipo_orden',
        'espacio_trabajo',
        'cliente_id',
        'vehiculo_id',
        'motivo_ingreso',
        'km_actual',
        'etapa_actual',
        'estado',
        'duracion_diagnostico',
        'diagnosticado_por',
        'detalle_diagnostico',
        'repuestos_entregados',
        'tiquete_impreso',
    ];

    protected $casts = [
        'km_actual' => 'integer',
        'espacio_trabajo' => 'integer',
        'duracion_diagnostico' => 'decimal:2',
        'repuestos_entregados' => 'boolean',
        'tiquete_impreso' => 'boolean',
    ];

    /**
     * Obtener los espacios de trabajo ocupados
     * Solo se consideran ocupados los espacios de órdenes activas (no cerradas ni finalizadas con entrega)
     */
    public static function espaciosOcupados(): array
    {
        return self::whereNotNull('espacio_trabajo')
            ->where('tipo_orden', 'Taller')
            ->whereNotIn('etapa_actual', ['Cerrada'])
            ->where(function ($query) {
                $query->where('estado', '!=', 'Cerrada')
                    ->orWhereNull('estado');
            })
            ->pluck('espacio_trabajo')
            ->toArray();
    }

    /**
     * Obtener los espacios de trabajo disponibles
     */
    public static function espaciosDisponibles(): array
    {
        $ocupados = self::espaciosOcupados();
        $disponibles = [];

        for ($i = 1; $i <= self::TOTAL_ESPACIOS; $i++) {
            if (!in_array($i, $ocupados)) {
                $disponibles[] = $i;
            }
        }

        return $disponibles;
    }

    /**
     * Verificar si un espacio está disponible
     */
    public static function espacioDisponible(int $espacio, ?int $exceptoOrdenId = null): bool
    {
        $query = self::where('espacio_trabajo', $espacio)
            ->where('tipo_orden', 'Taller')
            ->whereNotIn('etapa_actual', ['Cerrada'])
            ->where(function ($q) {
                $q->where('estado', '!=', 'Cerrada')
                    ->orWhereNull('estado');
            });

        if ($exceptoOrdenId) {
            $query->where('id', '!=', $exceptoOrdenId);
        }

        return !$query->exists();
    }

    /**
     * Liberar el espacio de trabajo de esta orden
     */
    public function liberarEspacio(): void
    {
        $this->espacio_trabajo = null;
        $this->save();
    }

    /**
     * Relación con cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con vehículo
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * Relación con fotografías
     */
    public function fotografias()
    {
        return $this->hasMany(FotografiaOrdenTrabajo::class);
    }

    /**
     * Relación con técnico que diagnosticó
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'diagnosticado_por');
    }

    /**
     * Relación con cotizaciones
     */
    public function cotizaciones()
    {
        return $this->hasMany(CotizacionOrdenTrabajo::class);
    }

    /**
     * Relación con órdenes de compra
     */
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompraOrdenTrabajo::class);
    }

    /**
     * Relación con facturas
     */
    public function facturas()
    {
        return $this->hasMany(FacturaOrdenTrabajo::class);
    }

    /**
     * Relación con adjuntos
     */
    public function adjuntos()
    {
        return $this->hasMany(OrdenTrabajoAdjunto::class);
    }

    /**
     * Relación con comentarios
     */
    public function comentarios()
    {
        return $this->hasMany(OrdenTrabajoComentario::class);
    }
}
