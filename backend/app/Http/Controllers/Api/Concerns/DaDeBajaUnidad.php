<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Fianza;
use App\Models\PagoAlquiler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait DaDeBajaUnidad
{
    /**
     * Da de baja al cliente de un trastero/piso. Si quedan pagos o fianzas
     * pendientes y no se ha forzado, devuelve un 409 con el detalle para
     * que el frontend pida confirmación antes de reintentarlo con force=1.
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
