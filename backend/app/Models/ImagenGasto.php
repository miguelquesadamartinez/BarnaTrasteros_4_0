<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagenGasto extends Model
{
    protected $table = 'imagenes_gastos';

    protected $fillable = [
        'gasto_id',
        'ruta',
        'nombre_original',
    ];

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class, 'gasto_id');
    }

    public function getUrlAttribute(): string
    {
        return url('storage/' . $this->ruta);
    }
}
