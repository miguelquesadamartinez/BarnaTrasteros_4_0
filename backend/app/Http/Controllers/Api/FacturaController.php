<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


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

        /**
     * Envía la factura del cliente por email (PDF adjunto)
     */
    public function enviarEmail(Request $request): JsonResponse
    {

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'mes' => 'required|integer|min:1|max:12',
            'anyo' => 'required|integer|min:2020',
        ]);

        $cliente = Cliente::findOrFail($request->cliente_id);
        if (!$cliente->email) {
            return response()->json(['message' => 'El cliente no tiene email registrado'], 422);
        }

        // Obtener pagos del mes
        $pagos = $cliente->pagosAlquiler()
            ->where('mes', $request->mes)
            ->where('anyo', $request->anyo)
            ->get();
        if ($pagos->isEmpty()) {
            return response()->json(['message' => 'No hay pagos para este cliente en ese mes'], 422);
        }

        $importe_total = $pagos->sum('importe_total');

        // Generar PDF (usando dompdf o snappy, aquí ejemplo simple)
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('emails.factura-cliente', [
            'cliente' => $cliente->toArray(),
            'mes' => $request->mes,
            'anyo' => $request->anyo,
            'mesNombre' => ucfirst(now()->setMonth($request->mes)->locale('es')->monthName),
            'pagos' => $pagos->toArray(),
            'importe_total' => $importe_total,
        ]);
        $pdfData = $pdf->output();

        Mail::to($cliente->email)
            ->send(new \App\Mail\FacturaClienteMail(
                $cliente->toArray(),
                $request->mes,
                $request->anyo,
                $pagos->toArray(),
                $importe_total,
                $pdfData
            ));

        return response()->json(['message' => 'Factura enviada correctamente']);
    }
}
