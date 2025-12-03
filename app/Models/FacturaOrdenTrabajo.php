<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaOrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'facturas_orden_trabajo';

    protected $fillable = [
        'orden_trabajo_id',
        'numero_factura',
        'alegra_id',
        'cliente_nombre',
        'fecha_emision',
        'total',
        'ruta_pdf',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }
}
