<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AvisoCambioPrecioMail;
use App\Mail\CambioPrecioMail;
use App\Models\HistorialPrecio;
use App\Models\Piso;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class RevisionPrecioController extends Controller
{
    public function historial(Request $request): JsonResponse
    {
        $historial = HistorialPrecio::orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json($historial);
    }

    public function aplicarTodos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'porcentaje' => 'required|numeric|between:-100,1000',
            'trasteros' => 'required|boolean',
            'pisos' => 'required|boolean',
        ]);

        if (!$validated['trasteros'] && !$validated['pisos']) {
            return response()->json(['message' => 'Selecciona al menos trasteros o pisos'], 422);
        }

        $porcentaje = (float) $validated['porcentaje'];
        $motivo = 'Revisión general ' . $this->formatearPorcentaje($porcentaje);
        $cambios = new Collection();

        if ($validated['trasteros']) {
            foreach (Trastero::all() as $trastero) {
                $cambio = $this->aplicarPorcentaje($trastero, $porcentaje, $motivo);
                if ($cambio) {
                    $cambios->push($cambio);
                }
            }
        }

        if ($validated['pisos']) {
            foreach (Piso::all() as $piso) {
                $cambio = $this->aplicarPorcentaje($piso, $porcentaje, $motivo);
                if ($cambio) {
                    $cambios->push($cambio);
                }
            }
        }

        Cache::tags(['trasteros', 'pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        if ($cambios->isNotEmpty()) {
            Mail::to((string) config('mail.reportes.pagos_to'))->queue(new CambioPrecioMail($cambios, $motivo));
            $this->avisarClientesCambioPrecio($cambios);
        }

        return response()->json(['actualizados' => $cambios->count()]);
    }

    private function aplicarPorcentaje(Trastero|Piso $unidad, float $porcentaje, string $motivo): ?HistorialPrecio
    {
        $precioAnterior = (float) $unidad->precio_mensual;
        $precioNuevo = round($precioAnterior * (1 + $porcentaje / 100), 2);

        if ($precioNuevo === $precioAnterior) {
            return null;
        }

        $unidad->update(['precio_mensual' => $precioNuevo]);

        return HistorialPrecio::create([
            'tipo' => $unidad instanceof Trastero ? 'trastero' : 'piso',
            'referencia_id' => $unidad->id,
            'numero' => $unidad->numero,
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'porcentaje' => $porcentaje,
            'motivo' => $motivo,
        ]);
    }

    private function formatearPorcentaje(float $porcentaje): string
    {
        return ($porcentaje >= 0 ? '+' : '') . rtrim(rtrim(number_format($porcentaje, 2, '.', ''), '0'), '.') . '%';
    }

    private function avisarClientesCambioPrecio(Collection $cambios): void
    {
        $trasterosMap = Trastero::with('cliente')
            ->whereIn('id', $cambios->where('tipo', 'trastero')->pluck('referencia_id'))
            ->get()->keyBy('id');

        $pisosMap = Piso::with('cliente')
            ->whereIn('id', $cambios->where('tipo', 'piso')->pluck('referencia_id'))
            ->get()->keyBy('id');

        $porCliente = collect();

        foreach ($cambios as $cambio) {
            $unidad = $cambio->tipo === 'trastero'
                ? $trasterosMap->get($cambio->referencia_id)
                : $pisosMap->get($cambio->referencia_id);

            if (!$unidad || !$unidad->cliente || !$unidad->cliente->email) {
                continue;
            }

            if (!$porCliente->has($unidad->cliente_id)) {
                $porCliente->put($unidad->cliente_id, ['cliente' => $unidad->cliente, 'filas' => collect()]);
            }
            $porCliente[$unidad->cliente_id]['filas']->push($cambio);
        }

        foreach ($porCliente as $datos) {
            Mail::to($datos['cliente']->email)->queue(new AvisoCambioPrecioMail($datos['cliente']->toArray(), $datos['filas']));
        }
    }

    public function aplicarIndividual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => 'required|in:trastero,piso',
            'referencia_id' => 'required|integer',
            'precio_nuevo' => 'required|numeric|min:0',
        ]);

        $unidad = $validated['tipo'] === 'trastero'
            ? Trastero::findOrFail($validated['referencia_id'])
            : Piso::findOrFail($validated['referencia_id']);

        $precioAnterior = (float) $unidad->precio_mensual;
        $precioNuevo = round((float) $validated['precio_nuevo'], 2);

        if ($precioNuevo === $precioAnterior) {
            return response()->json(['message' => 'El precio no ha cambiado'], 422);
        }

        $unidad->update(['precio_mensual' => $precioNuevo]);

        $porcentaje = $precioAnterior > 0 ? round((($precioNuevo - $precioAnterior) / $precioAnterior) * 100, 2) : null;
        $motivo = trim('Actualización manual ' . ($porcentaje !== null ? $this->formatearPorcentaje($porcentaje) : ''));

        $cambio = HistorialPrecio::create([
            'tipo' => $validated['tipo'],
            'referencia_id' => $unidad->id,
            'numero' => $unidad->numero,
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'porcentaje' => $porcentaje,
            'motivo' => $motivo,
        ]);

        Cache::tags(['trasteros', 'pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        Mail::to((string) config('mail.reportes.pagos_to'))->queue(new CambioPrecioMail(collect([$cambio]), $motivo));
        $this->avisarClientesCambioPrecio(collect([$cambio]));

        return response()->json($unidad);
    }
}
