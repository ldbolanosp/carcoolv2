<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'ordenes_trabajo';

    protected $fillable = [
        'tipo_orden',
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
        'duracion_diagnostico' => 'decimal:2',
        'repuestos_entregados' => 'boolean',
        'tiquete_impreso' => 'boolean',
    ];

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
