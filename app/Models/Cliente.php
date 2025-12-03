<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_identificacion',
        'numero_identificacion',
        'nombre',
        'correo_electronico',
        'telefono',
        'direccion',
    ];
}
