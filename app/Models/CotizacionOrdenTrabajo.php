<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionOrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones_orden_trabajo';

    protected $fillable = [
        'orden_trabajo_id',
        'numero_cotizacion',
        'alegra_id',
        'cliente_nombre',
        'fecha_emision',
        'total',
        'ruta_pdf',
        'aprobada',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'aprobada' => 'boolean',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }
}
