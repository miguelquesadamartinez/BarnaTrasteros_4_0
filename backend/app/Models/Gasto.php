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

    // Número visible (T-06, P-2A) de la unidad, para no mostrar el id interno.
    protected $appends = ['referencia_numero'];

    protected $casts = [
        'importe_total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'fecha_emision' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleGasto::class, 'gasto_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenGasto::class, 'gasto_id');
    }

    public function getReferenciaNumeroAttribute(): ?string
    {
        if (!$this->referencia_id || !in_array($this->referencia_tipo, ['trastero', 'piso'], true)) {
            return null;
        }
        $modelo = $this->referencia_tipo === 'trastero' ? Trastero::class : Piso::class;

        return $modelo::whereKey($this->referencia_id)->value('numero');
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
