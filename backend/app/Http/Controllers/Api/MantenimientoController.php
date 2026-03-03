<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerarPagosMensuales;
use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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

        // Contar registros existentes antes
        $antes = PagoAlquiler::where('mes', $mes)->where('anyo', $anyo)->count();

        GenerarPagosMensuales::dispatchSync($mes, $anyo);

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

    /**
     * Lista los backups disponibles en mysql_bk.
     */
    public function listarBackups(): JsonResponse
    {
        $dir   = base_path('mysql_bk');
        $files = File::isDirectory($dir) ? glob($dir . '/*.sql.gz') : [];

        $backups = array_map(function ($path) {
            return [
                'filename' => basename($path),
                'size_kb'  => round(filesize($path) / 1024, 1),
                'fecha'    => date('Y-m-d H:i:s', filemtime($path)),
            ];
        }, $files);

        // Más reciente primero
        usort($backups, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

        return response()->json($backups);
    }

    /**
     * Genera un backup de la base de datos.
     */
    public function backup(): JsonResponse
    {
        $exitCode = Artisan::call('db:backup');

        if ($exitCode !== 0) {
            return response()->json(['error' => 'Error al generar el backup.'], 500);
        }

        $output = trim(Artisan::output());

        return response()->json([
            'ok'      => true,
            'mensaje' => $output ?: 'Backup generado correctamente.',
        ]);
    }

    /**
     * Restaura la base de datos desde un backup.
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = basename($request->filename); // seguridad: no permitir ../
        $filepath = base_path('mysql_bk/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json(['error' => "El archivo '{$filename}' no existe."], 422);
        }

        $exitCode = Artisan::call('db:restore', ['filename' => $filename]);

        if ($exitCode !== 0) {
            return response()->json(['error' => 'Error al restaurar el backup.'], 500);
        }

        return response()->json([
            'ok'      => true,
            'mensaje' => "Base de datos restaurada desde: {$filename}",
        ]);
    }
    /**
     * Elimina un archivo de backup.
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = basename($request->filename);
        $filepath = base_path('mysql_bk/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json(['error' => "El archivo '{$filename}' no existe."], 422);
        }

        unlink($filepath);

        return response()->json([
            'ok'      => true,
            'mensaje' => "Backup '{$filename}' eliminado correctamente.",
        ]);
    }
}
