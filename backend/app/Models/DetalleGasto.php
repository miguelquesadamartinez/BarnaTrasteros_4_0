<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleGasto extends Model
{
    protected $table = 'detalle_gastos';

    protected $fillable = [
        'gasto_id',
        'importe',
        'fecha_pago',
        'notas',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class, 'gasto_id');
    }
}
