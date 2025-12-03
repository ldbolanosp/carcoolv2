<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoAdjunto extends Model
{
    use HasFactory;

    protected $table = 'orden_trabajo_adjuntos';

    protected $fillable = [
        'orden_trabajo_id',
        'ruta_archivo',
        'nombre_archivo',
        'tipo_mime',
        'tamaño',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }
}
