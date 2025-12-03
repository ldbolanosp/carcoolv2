<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoComentario extends Model
{
    use HasFactory;

    protected $table = 'orden_trabajo_comentarios';

    protected $fillable = [
        'orden_trabajo_id',
        'user_id',
        'comentario',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
