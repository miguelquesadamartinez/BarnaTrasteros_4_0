<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trastero extends Model
{
    protected $table = 'trasteros';

    protected $fillable = [
        'numero',
        'piso',
        'tamanyo',
        'precio_mensual',
        'cliente_id',
        'fecha_inicio_alquiler',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio_alquiler' => 'date:Y-m-d',
        'precio_mensual' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function pagosAlquiler(): HasMany
    {
        return $this->hasMany(PagoAlquiler::class, 'referencia_id')
            ->where('tipo', 'trastero');
    }
}
