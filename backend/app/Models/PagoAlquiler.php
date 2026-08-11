<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoAlquiler extends Model
{
    protected $table = 'pagos_alquiler';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'referencia_id',
        'numero',
        'mes',
        'anyo',
        'importe_total',
        'pagado',
        'estado',
        'notas',
    ];

    protected $casts = [
        'importe_total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'mes' => 'integer',
        'anyo' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePagoAlquiler::class, 'pago_alquiler_id');
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

    public function enviarAvisoImpago(): bool
    {
        $this->loadMissing('cliente');

        if (!$this->cliente || !$this->cliente->email) {
            return false;
        }

        $pendiente = max(0, (float) $this->importe_total - (float) $this->pagado);
        $mesNombre = ucfirst(\Carbon\Carbon::create()->month($this->mes)->locale('es')->monthName);

        \Illuminate\Support\Facades\Mail::to($this->cliente->email)->queue(new \App\Mail\AvisoImpagoMail(
            $this->cliente->toArray(),
            $this->toArray(),
            $mesNombre,
            $pendiente
        ));

        $this->aviso_impago_enviado_at = now();
        $this->save();

        return true;
    }
}
