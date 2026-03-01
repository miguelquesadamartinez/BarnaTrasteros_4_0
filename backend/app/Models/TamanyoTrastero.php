<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamanyoTrastero extends Model
{
    protected $table = 'tamanyo_trasteros';

    protected $fillable = ['nombre', 'descripcion', 'orden', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];
}
