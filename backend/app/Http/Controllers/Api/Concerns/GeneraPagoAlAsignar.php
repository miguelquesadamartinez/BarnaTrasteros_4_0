<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

trait GeneraPagoAlAsignar
{
    /**
     * Al asignar una unidad a un cliente se genera ya el pago del mes en
     * curso (en vez de esperar al job del día 1), para que un cambio de
     * inquilino a mitad de mes no se quede sin cobrar. El índice único de
     * pagos_alquiler incluye cliente_id, así que esto no choca con un pago
     * que ya existiera ese mismo mes para el inquilino anterior.
     */
    protected function generarPagoDelMes(Model $unidad, string $tipo, int $clienteId, ?float $importeOverride = null): void
    {
        $ahora = Carbon::now();

        $existe = PagoAlquiler::where('tipo', $tipo)
            ->where('referencia_id', $unidad->id)
            ->where('cliente_id', $clienteId)
            ->where('mes', $ahora->month)
            ->where('anyo', $ahora->year)
            ->exists();

        if ($existe) {
            return;
        }

        if ($importeOverride !== null) {
            $importe = round($importeOverride, 2);
        } else {
            $fechaInicio = $unidad->fecha_inicio_alquiler ? Carbon::parse($unidad->fecha_inicio_alquiler) : $ahora;
            $importe = $this->calcularProrrateo((float) $unidad->precio_mensual, $fechaInicio, $ahora->copy()->endOfMonth(), $ahora->month, $ahora->year);
        }

        PagoAlquiler::create([
            'cliente_id'    => $clienteId,
            'tipo'          => $tipo,
            'referencia_id' => $unidad->id,
            'numero'        => $unidad->numero,
            'mes'           => $ahora->month,
            'anyo'          => $ahora->year,
            'importe_total' => $importe,
            'pagado'        => 0,
            'estado'        => 'pendiente',
        ]);
    }

    /**
     * Importe proporcional a los días facturables dentro del mes/año dados,
     * entre max(fechaInicio, día 1 del mes) y min(fechaFin, último día del mes),
     * ambos inclusive. Se usa tanto para el primer pago de un inquilino nuevo
     * (fechaFin = fin de mes) como para sugerir el importe final de uno que se
     * da de baja a mitad de mes (fechaFin = hoy).
     */
    protected function calcularProrrateo(float $precioMensual, Carbon $fechaInicio, Carbon $fechaFin, int $mes, int $anyo): float
    {
        $inicioMes = Carbon::create($anyo, $mes, 1)->startOfDay();
        $diasEnMes = $inicioMes->daysInMonth;
        $ultimoDiaMes = $inicioMes->copy()->addDays($diasEnMes - 1);

        $desde = $fechaInicio->copy()->startOfDay();
        if ($desde->lessThan($inicioMes)) {
            $desde = $inicioMes->copy();
        }

        $hasta = $fechaFin->copy()->startOfDay();
        if ($hasta->greaterThan($ultimoDiaMes)) {
            $hasta = $ultimoDiaMes->copy();
        }

        if ($hasta->lessThan($desde)) {
            return 0;
        }

        $diasFacturables = $desde->diffInDays($hasta) + 1;

        return round($precioMensual * $diasFacturables / $diasEnMes, 2);
    }
}
