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
        'email',
        'foto_dni',
        'contrato_path',
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

    public function fianzas(): HasMany
    {
        return $this->hasMany(Fianza::class, 'cliente_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function enviarAvisoImpago(): bool
    {
        if (!$this->email) {
            return false;
        }

        $pagos = $this->pagosAlquiler()->whereIn('estado', ['pendiente', 'parcial'])->get();
        if ($pagos->isEmpty()) {
            return false;
        }

        $filas = $pagos->map(function (PagoAlquiler $pago) {
            return [
                'tipo' => $pago->tipo,
                'numero' => $pago->numero ?? $pago->referencia_id,
                'mesNombre' => ucfirst(\Carbon\Carbon::create()->month($pago->mes)->locale('es')->monthName),
                'anyo' => $pago->anyo,
                'pendiente' => max(0, (float) $pago->importe_total - (float) $pago->pagado),
            ];
        })->values();

        $totalPendiente = $filas->sum('pendiente');

        \Illuminate\Support\Facades\Mail::to($this->email)->queue(new \App\Mail\AvisoImpagoMail(
            $this->toArray(),
            $filas->toArray(),
            $totalPendiente
        ));

        PagoAlquiler::whereIn('id', $pagos->pluck('id'))->update(['aviso_impago_enviado_at' => now()]);

        return true;
    }
}
