<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePagoAlquiler extends Model
{
    protected $table = 'detalle_pagos_alquiler';

    protected $fillable = [
        'pago_alquiler_id',
        'importe',
        'fecha_pago',
        'notas',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function pagoAlquiler(): BelongsTo
    {
        return $this->belongsTo(PagoAlquiler::class, 'pago_alquiler_id');
    }
}
