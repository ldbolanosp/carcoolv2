<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'placa',
        'marca_id',
        'modelo_id',
        'ano',
        'color',
        'numero_chasis',
        'numero_unidad',
    ];

    protected $casts = [
        'ano' => 'integer',
    ];

    /**
     * Relación con marca
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    /**
     * Relación con modelo
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}
