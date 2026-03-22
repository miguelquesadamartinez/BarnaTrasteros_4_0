<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FacturaController extends Controller
{
    /**
     * Devuelve, para un mes/año dado, todos los clientes con necesita_factura=true
     * junto con sus pagos de alquiler de ese período.
     */
    public function index(Request $request): JsonResponse
    {
        $mes  = $request->integer('mes',  now()->month);
        $anyo = $request->integer('anyo', now()->year);

        $result = Cache::tags(['facturas', 'clientes', 'pagos-alquiler'])->remember(
            "facturas:mes:{$mes}:anyo:{$anyo}",
            now()->addHours(24),
            function () use ($mes, $anyo) {
                $clientes = Cliente::where('necesita_factura', true)
                    ->with([
                        'trasteros',
                        'pisos',
                        'pagosAlquiler' => function ($q) use ($mes, $anyo) {
                            $q->where('mes', $mes)->where('anyo', $anyo)
                              ->with('detalles');
                        },
                    ])
                    ->get();

                return $clientes->filter(fn ($c) => $c->pagosAlquiler->isNotEmpty())
                    ->map(function ($cliente) {
                        return [
                            'cliente'       => $cliente->only(['id','nombre','apellido','dni','telefono','direccion','codigo_postal','ciudad','necesita_factura']),
                            'pagos'         => $cliente->pagosAlquiler->map(fn ($p) => $p->toArray())->values(),
                            'importe_total' => $cliente->pagosAlquiler->sum('importe_total'),
                            'total_pagado'  => $cliente->pagosAlquiler->sum('pagado'),
                        ];
                    })
                    ->values();
            }
        );

        return response()->json([
            'mes'      => $mes,
            'anyo'     => $anyo,
            'facturas' => $result,
        ]);
    }
}
