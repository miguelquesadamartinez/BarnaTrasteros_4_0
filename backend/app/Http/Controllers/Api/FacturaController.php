<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Trastero;
use App\Models\Piso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * Devuelve, para un mes/año dado, todos los clientes con necesita_factura=true
     * junto con sus pagos de alquiler de ese período.
     */
    public function index(Request $request): JsonResponse
    {
        $mes  = (int) $request->get('mes',  now()->month);
        $anyo = (int) $request->get('anyo', now()->year);

        // Clientes que necesitan factura
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

        // Solo los que tienen pagos ese mes
        $result = $clientes->filter(fn ($c) => $c->pagosAlquiler->isNotEmpty())
            ->map(function ($cliente) {
                $pagos = $cliente->pagosAlquiler->map(function ($pago) {
                    $data = $pago->toArray();
                    if ($pago->tipo === 'trastero') {
                        $data['numero'] = Trastero::find($pago->referencia_id)?->numero ?? $pago->referencia_id;
                    } else {
                        $data['numero'] = Piso::find($pago->referencia_id)?->numero ?? $pago->referencia_id;
                    }
                    return $data;
                });
                return [
                    'cliente'       => $cliente->only(['id','nombre','apellido','dni','telefono','direccion','codigo_postal','ciudad','necesita_factura']),
                    'pagos'         => $pagos->values(),
                    'importe_total' => $cliente->pagosAlquiler->sum('importe_total'),
                    'total_pagado'  => $cliente->pagosAlquiler->sum('pagado'),
                ];
            })
            ->values();

        return response()->json([
            'mes'      => $mes,
            'anyo'     => $anyo,
            'facturas' => $result,
        ]);
    }
}
