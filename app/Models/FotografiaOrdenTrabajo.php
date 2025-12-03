<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotografiaOrdenTrabajo extends Model
{
    use HasFactory;

    protected $table = 'fotografias_orden_trabajo';

    protected $fillable = [
        'orden_trabajo_id',
        'ruta_archivo',
        'nombre_archivo',
        'tipo_mime',
        'tamaño',
        'descripcion',
    ];

    /**
     * Relación con orden de trabajo
     */
    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }
}
