<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Fianza;
use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait DaDeBajaUnidad
{
    /**
     * Da de baja al cliente de un trastero/piso. Si quedan pagos o fianzas
     * pendientes y no se ha forzado, devuelve un 409 con el detalle para
     * que el frontend pida confirmación antes de reintentarlo con force=1.
     *
     * Si se envía `importe_final`, se aplica como importe_total del pago del
     * mes en curso de este cliente (creándolo si aún no existe) — así se deja
     * constancia de cuánto debía pagar por el periodo hasta la baja, en vez
     * de dejarle cargado el mes completo o nada.
     */
    protected function darDeBaja(Request $request, Model $unidad, string $tipo): JsonResponse
    {
        if ($unidad->cliente_id === null) {
            return response()->json([
                'message' => 'Esta unidad no tiene ningún cliente asignado.',
            ], 422);
        }

        $pagosPendientes = PagoAlquiler::where('tipo', $tipo)
            ->where('referencia_id', $unidad->id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->get(['id', 'mes', 'anyo', 'importe_total', 'pagado', 'estado']);

        $fianzasPendientes = Fianza::where('tipo', $tipo)
            ->where('referencia_id', $unidad->id)
            ->where('devuelta', false)
            ->get(['id', 'importe', 'fecha_entrega']);

        if (!$request->boolean('force') && ($pagosPendientes->isNotEmpty() || $fianzasPendientes->isNotEmpty())) {
            return response()->json([
                'requiere_confirmacion' => true,
                'pagos_pendientes' => $pagosPendientes,
                'fianzas_pendientes' => $fianzasPendientes,
            ], 409);
        }

        if ($request->filled('importe_final')) {
            $ahora = Carbon::now();
            $pago = PagoAlquiler::firstOrNew([
                'tipo' => $tipo,
                'referencia_id' => $unidad->id,
                'cliente_id' => $unidad->cliente_id,
                'mes' => $ahora->month,
                'anyo' => $ahora->year,
            ]);
            $pago->numero = $unidad->numero;
            $pago->importe_total = round((float) $request->input('importe_final'), 2);
            if (!$pago->exists) {
                $pago->pagado = 0;
            }
            $pago->save();
            $pago->recalcularEstado();
        }

        $clienteAnterior = $unidad->cliente;
        $nota = 'Baja de ' . ($clienteAnterior->nombre_completo ?? 'cliente') . ' el ' . now()->format('d/m/Y') . '.';
        $unidad->notas = trim(($unidad->notas ? $unidad->notas . "\n" : '') . $nota);
        $unidad->cliente_id = null;
        $unidad->fecha_inicio_alquiler = null;
        $unidad->fecha_vencimiento = null;
        $unidad->save();

        return response()->json($unidad->load('cliente'));
    }
}
