<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialPrecio extends Model
{
    protected $table = 'historial_precios';

    protected $fillable = [
        'tipo',
        'referencia_id',
        'numero',
        'precio_anterior',
        'precio_nuevo',
        'porcentaje',
        'motivo',
    ];

    protected $casts = [
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2',
        'porcentaje' => 'decimal:2',
    ];
}
