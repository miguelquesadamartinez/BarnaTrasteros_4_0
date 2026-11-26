<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BackupDatabaseMail;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups)
    {
    }

    /** Listado de copias + resumen para la pantalla. */
    public function index(): JsonResponse
    {
        $lista = $this->backups->listar();

        return response()->json([
            'backups' => $lista,
            'resumen' => [
                'total'            => count($lista),
                'bytes'            => array_sum(array_column($lista, 'bytes')),
                'ultima'           => $lista[0] ?? null,
                'ultima_completa'  => collect($lista)->firstWhere('tipo', 'completo'),
                'hora_automatica'  => config('backups.hora_automatica'),
                'retencion'        => (int) config('backups.retencion_automaticas'),
                'email_max_mb'     => (int) config('backups.email_max_mb'),
                'email_configurado'=> (bool) env('EMAIL_APP_DATA'),
            ],
            'actual' => $this->backups->estadoActual(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(BackupService::TIPOS)],
        ]);

        return $this->ejecutar(fn () => [
            'ok'     => true,
            'backup' => $this->backups->crear($datos['tipo']),
        ], 201);
    }

    public function descargar(string $filename): BinaryFileResponse|JsonResponse
    {
        try {
            return response()->download($this->backups->ruta($filename));
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function verificar(string $filename): JsonResponse
    {
        return $this->ejecutar(fn () => $this->backups->verificar($filename));
    }

    public function restaurar(Request $request, string $filename): JsonResponse
    {
        $datos = $request->validate([
            'bd'           => 'required|boolean',
            'archivos'     => 'required|boolean',
            'copia_previa' => 'nullable|boolean',
        ]);

        // Una restauración grande puede tardar más que el límite por defecto de PHP.
        set_time_limit(600);

        return $this->ejecutar(fn () => ['ok' => true] + $this->backups->restaurar(
            $filename,
            (bool) $datos['bd'],
            (bool) $datos['archivos'],
            $datos['copia_previa'] ?? true,
        ));
    }

    public function subir(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|max:51200',
        ], [
            'archivo.max' => 'La copia no puede superar los 50 MB.',
        ]);

        $archivo = $request->file('archivo');

        return $this->ejecutar(fn () => [
            'ok'     => true,
            'backup' => $this->backups->importar($archivo->getRealPath(), $archivo->getClientOriginalName()),
        ], 201);
    }

    public function email(string $filename): JsonResponse
    {
        $to = env('EMAIL_APP_DATA');
        if (!$to) {
            return response()->json(['error' => 'No se ha configurado EMAIL_APP_DATA en el .env.'], 422);
        }

        return $this->ejecutar(function () use ($filename, $to) {
            $ruta = $this->backups->ruta($filename);
            $maxMb = (int) config('backups.email_max_mb');
            if (filesize($ruta) > $maxMb * 1024 * 1024) {
                throw new RuntimeException("La copia supera {$maxMb} MB y no cabe en un email. Descárgala en su lugar.");
            }
            Mail::to($to)->send(new BackupDatabaseMail($ruta));

            return ['ok' => true, 'mensaje' => "Copia enviada por email a {$to}."];
        });
    }

    public function destroy(string $filename): JsonResponse
    {
        return $this->ejecutar(function () use ($filename) {
            $this->backups->eliminar($filename);

            return ['ok' => true];
        });
    }

    /** Convierte los errores esperables del servicio en un 422 con mensaje legible. */
    private function ejecutar(callable $accion, int $codigoOk = 200): JsonResponse
    {
        try {
            return response()->json($accion(), $codigoOk);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
