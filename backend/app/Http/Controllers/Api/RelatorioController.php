<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use App\Models\PagoAlquiler;
use App\Models\Piso;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    /**
     * Estado actual de todos los trasteros: si está alquilado y a quién.
     */
    public function estadoTrasteros(): JsonResponse
    {
        $trasteros = Trastero::with('cliente')->orderBy('numero')->get();

        $lista = $trasteros->map(function ($t) {
            return [
                'id'                    => $t->id,
                'numero'                => $t->numero,
                'piso'                  => $t->piso,
                'tamanyo'               => $t->tamanyo,
                'precio_mensual'        => $t->precio_mensual,
                'cliente_id'            => $t->cliente_id,
                'cliente'               => $t->cliente ? [
                    'id'       => $t->cliente->id,
                    'nombre'   => $t->cliente->nombre,
                    'apellido' => $t->cliente->apellido,
                    'dni'      => $t->cliente->dni,
                    'telefono' => $t->cliente->telefono,
                ] : null,
                'fecha_inicio_alquiler' => $t->fecha_inicio_alquiler,
            ];
        });

        return response()->json([
            'total'      => $trasteros->count(),
            'alquilados' => $trasteros->whereNotNull('cliente_id')->count(),
            'libres'     => $trasteros->whereNull('cliente_id')->count(),
            'lista'      => $lista,
        ]);
    }

    /**
     * Estado actual de todos los pisos: si está alquilado y a quién.
     */
    public function estadoPisos(): JsonResponse
    {
        $pisos = Piso::with('cliente')->orderBy('numero')->get();

        $lista = $pisos->map(function ($p) {
            return [
                'id'                    => $p->id,
                'numero'                => $p->numero,
                'piso'                  => $p->piso,
                'precio_mensual'        => $p->precio_mensual,
                'cliente_id'            => $p->cliente_id,
                'cliente'               => $p->cliente ? [
                    'id'       => $p->cliente->id,
                    'nombre'   => $p->cliente->nombre,
                    'apellido' => $p->cliente->apellido,
                    'dni'      => $p->cliente->dni,
                    'telefono' => $p->cliente->telefono,
                ] : null,
                'fecha_inicio_alquiler' => $p->fecha_inicio_alquiler,
            ];
        });

        return response()->json([
            'total'      => $pisos->count(),
            'alquilados' => $pisos->whereNotNull('cliente_id')->count(),
            'libres'     => $pisos->whereNull('cliente_id')->count(),
            'lista'      => $lista,
        ]);
    }

    /**
     * Estado de los pagos de alquiler: pendientes, parciales, pagados.
     */
    public function estadoPagos(Request $request): JsonResponse
    {
        $query = PagoAlquiler::with('cliente');

        if ($request->filled('anyo')) {
            $query->where('anyo', $request->anyo);
        }
        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $pagos = $query->orderBy('anyo', 'desc')->orderBy('mes', 'desc')->orderBy('tipo')->get();

        $totalPendiente = $pagos->whereIn('estado', ['pendiente', 'parcial'])
            ->sum(fn($p) => $p->importe_total - $p->pagado);

        return response()->json([
            'total'           => $pagos->count(),
            'pendientes'      => $pagos->whereIn('estado', ['pendiente', 'parcial'])->count(),
            'pagados'         => $pagos->where('estado', 'pagado')->count(),
            'total_pendiente' => round($totalPendiente, 2),
            'lista'           => $pagos,
        ]);
    }

    /**
     * Estado de gastos (agua, luz, etc.)
     */
    public function estadoGastos(Request $request): JsonResponse
    {
        $query = Gasto::with(['detalles', 'imagenes']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $gastos = $query->orderBy('fecha_emision', 'desc')->get();

        $gastos->each(function ($gasto) {
            $gasto->imagenes->each(function ($imagen) {
                $imagen->url = url('storage/' . $imagen->ruta);
            });
        });

        $totalPendiente = $gastos->whereIn('estado', ['pendiente', 'parcial'])
            ->sum(fn($g) => $g->importe_total - $g->pagado);

        return response()->json([
            'total'           => $gastos->count(),
            'pendientes'      => $gastos->whereIn('estado', ['pendiente', 'parcial'])->count(),
            'pagados'         => $gastos->where('estado', 'pagado')->count(),
            'total_pendiente' => round($totalPendiente, 2),
            'lista'           => $gastos,
        ]);
    }

    /**
     * Resumen general para el dashboard.
     */
    public function resumenGeneral(): JsonResponse
    {
        $totalTrasteros  = Trastero::count();
        $trasterosLibres = Trastero::whereNull('cliente_id')->count();
        $trasterosAlquilados = $totalTrasteros - $trasterosLibres;

        $totalPisos  = Piso::count();
        $pisosLibres = Piso::whereNull('cliente_id')->count();
        $pisosAlquilados = $totalPisos - $pisosLibres;

        $pagosPendientes = PagoAlquiler::whereIn('estado', ['pendiente', 'parcial'])
            ->selectRaw('SUM(importe_total - pagado) as total_pendiente')
            ->value('total_pendiente') ?? 0;

        $gastosPendientes = Gasto::whereIn('estado', ['pendiente', 'parcial'])
            ->selectRaw('SUM(importe_total - pagado) as total_pendiente')
            ->value('total_pendiente') ?? 0;

        return response()->json([
            'trasteros' => [
                'total'      => $totalTrasteros,
                'alquilados' => $trasterosAlquilados,
                'libres'     => $trasterosLibres,
            ],
            'pisos' => [
                'total'      => $totalPisos,
                'alquilados' => $pisosAlquilados,
                'libres'     => $pisosLibres,
            ],
            'pagos_pendientes'  => round($pagosPendientes, 2),
            'gastos_pendientes' => round($gastosPendientes, 2),
        ]);
    }
}
