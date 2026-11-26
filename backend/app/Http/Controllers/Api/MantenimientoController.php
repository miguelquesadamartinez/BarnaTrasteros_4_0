<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerarPagosMensuales;
use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MantenimientoController extends Controller
{
    /**
     * Genera los pagos mensuales de alquiler para el mes/año indicado.
     * Si no se pasan parámetros, usa el mes/año actual.
     */
    public function generarPagos(Request $request): JsonResponse
    {
        $ahora = Carbon::now();

        $mes  = (int) $request->input('mes',  $ahora->month);
        $anyo = (int) $request->input('anyo', $ahora->year);

        if ($mes < 1 || $mes > 12) {
            return response()->json(['error' => 'El mes debe estar entre 1 y 12.'], 422);
        }
        if ($anyo < 2000 || $anyo > 2100) {
            return response()->json(['error' => 'El año no es válido.'], 422);
        }
        // Se generan con el inquilino y precio actuales: un mes futuro produciría
        // recibos (y avisos de impago) por meses que aún no han llegado.
        if ($anyo * 12 + $mes > $ahora->year * 12 + $ahora->month) {
            return response()->json(['error' => 'No se pueden generar pagos de meses futuros.'], 422);
        }

        // Contar registros existentes antes
        $antes = PagoAlquiler::where('mes', $mes)->where('anyo', $anyo)->count();

        GenerarPagosMensuales::dispatchSync($mes, $anyo);

        Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

        // Contar registros después para informar cuántos se crearon
        $despues  = PagoAlquiler::where('mes', $mes)->where('anyo', $anyo)->count();
        $creados  = $despues - $antes;

        return response()->json([
            'ok'      => true,
            'mes'     => $mes,
            'anyo'    => $anyo,
            'creados' => $creados,
            'total'   => $despues,
            'mensaje' => $creados > 0
                ? "Se han generado {$creados} pago(s) para {$mes}/{$anyo}."
                : "Todos los pagos de {$mes}/{$anyo} ya existían. No se creó ninguno nuevo.",
        ]);
    }
}
