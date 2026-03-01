<?php

namespace App\Models;

use App\Models\ImagenGasto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'tipo',
        'descripcion',
        'referencia_tipo',
        'referencia_id',
        'fecha_emision',
        'fecha_vencimiento',
        'importe_total',
        'pagado',
        'estado',
        'notas',
    ];

    protected $casts = [
        'importe_total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleGasto::class, 'gasto_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenGasto::class, 'gasto_id');
    }

    public function getPendienteAttribute(): float
    {
        return max(0, $this->importe_total - $this->pagado);
    }

    public function recalcularEstado(): void
    {
        if ($this->pagado <= 0) {
            $this->estado = 'pendiente';
        } elseif ($this->pagado >= $this->importe_total) {
            $this->estado = 'pagado';
        } else {
            $this->estado = 'parcial';
        }
        $this->save();
    }
}
