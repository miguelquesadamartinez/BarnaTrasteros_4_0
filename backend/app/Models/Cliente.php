<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'dni',
        'foto_dni',
        'direccion',
        'codigo_postal',
        'ciudad',
        'necesita_factura',
    ];

    protected $casts = [
        'necesita_factura' => 'boolean',
    ];

    public function trasteros(): HasMany
    {
        return $this->hasMany(Trastero::class, 'cliente_id');
    }

    public function pisos(): HasMany
    {
        return $this->hasMany(Piso::class, 'cliente_id');
    }

    public function pagosAlquiler(): HasMany
    {
        return $this->hasMany(PagoAlquiler::class, 'cliente_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
