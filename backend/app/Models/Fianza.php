<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fianza extends Model
{
    protected $table = 'fianzas';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'referencia_id',
        'numero',
        'importe',
        'fecha_entrega',
        'devuelta',
        'fecha_devolucion',
        'notas',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'fecha_entrega' => 'date:Y-m-d',
        'fecha_devolucion' => 'date:Y-m-d',
        'devuelta' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
