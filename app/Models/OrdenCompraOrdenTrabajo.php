<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompraOrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra_orden_trabajo';

    protected $fillable = [
        'orden_trabajo_id',
        'numero_orden',
        'alegra_id',
        'proveedor_nombre',
        'fecha_emision',
        'total',
        'ruta_pdf',
    ];

    /**
     * Relación con orden de trabajo
     */
    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }
}
