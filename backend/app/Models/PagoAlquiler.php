<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoAlquiler extends Model
{
    // Cada unidad tiene su propio día de vencimiento (fecha_vencimiento); si no
    // lo tiene fijado (unidades antiguas sin asignar), se usa el día 5 por defecto.
    private const DIA_VENCIMIENTO_POR_DEFECTO = 5;
    private const DIAS_MARGEN_AVISO = 5;

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

    /**
     * Si ya ha pasado el margen de gracia (días) desde la fecha de vencimiento
     * de la unidad, y por tanto es correcto avisar al cliente de este impago.
     */
    public function elegibleParaAvisoImpago(): bool
    {
        $unidad = $this->tipo === 'trastero'
            ? Trastero::find($this->referencia_id)
            : Piso::find($this->referencia_id);

        $dia = $unidad?->fecha_vencimiento?->day ?? self::DIA_VENCIMIENTO_POR_DEFECTO;
        $fechaAviso = Carbon::create($this->anyo, $this->mes, $dia)->addDays(self::DIAS_MARGEN_AVISO);

        return Carbon::now()->gte($fechaAviso);
    }
}
