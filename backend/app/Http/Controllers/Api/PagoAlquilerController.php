<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetallePagoAlquiler;
use App\Models\PagoAlquiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PagoAlquilerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'pagos:list:' . md5(serialize($request->only([
            'tipo', 'referencia_id', 'cliente_id', 'cliente',
            'estado', 'anyo', 'mes', 'per_page', 'page',
        ])));

        $pagos = Cache::tags(['pagos-alquiler'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
            $query = PagoAlquiler::with(['cliente', 'detalles']);

            if ($request->has('tipo') && $request->tipo) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('referencia_id') && $request->referencia_id) {
                $query->where('referencia_id', $request->referencia_id);
            }

            if ($request->has('cliente_id') && $request->cliente_id) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->filled('cliente')) {
                $search = $request->cliente;
                $query->whereHas('cliente', function ($q) use ($search) {
                    $q->whereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$search}%"])
                      ->orWhere('nombre',   'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('dni',      'like', "%{$search}%");
                });
            }

            if ($request->has('estado') && $request->estado) {
                $estados = array_filter(array_map('trim', explode(',', $request->estado)));
                if (count($estados) === 1) {
                    $query->where('estado', $estados[0]);
                } else {
                    $query->whereIn('estado', $estados);
                }
            }

            if ($request->has('anyo') && $request->anyo) {
                $query->where('anyo', $request->anyo);
            }

            if ($request->has('mes') && $request->mes) {
                $query->where('mes', $request->mes);
            }

            $perPage = $request->integer('per_page', 15);

            return $query->orderBy('anyo', 'desc')->orderBy('mes', 'desc')->paginate($perPage);
        });

        return response()->json($pagos);
    }

    public function show(PagoAlquiler $pagoAlquiler): JsonResponse
    {
        $data = Cache::tags(['pagos-alquiler'])->remember("pagos-alquiler:show:{$pagoAlquiler->id}", now()->addHours(24), function () use ($pagoAlquiler) {
            return $pagoAlquiler->load(['cliente', 'detalles']);
        });

        return response()->json($data);
    }

    /**
     * Registrar un pago - distribuye automáticamente entre TODOS los meses pendientes del cliente
     * (pisos y trasteros), ordenados del más antiguo al más reciente.
     * No se permite pagar más del total pendiente del cliente.
     */
    public function registrarPago(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'   => 'required|exists:clientes,id',
            'importe'      => 'required|numeric|min:0.01',
            'fecha_pago'   => 'required|date',
            'notas'        => 'nullable|string',
        ]);

        $clienteId       = $validated['cliente_id'];
        $importeAplicar  = (float) $validated['importe'];
        $fechaPago       = $validated['fecha_pago'];
        $notas           = $validated['notas'] ?? null;

        // Obtener todos los pagos pendientes o parciales del cliente (pisos y trasteros)
        $pagosPendientes = PagoAlquiler::where('cliente_id', $clienteId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('anyo')
            ->orderBy('mes')
            ->get();

        // Calcular el total pendiente del cliente
        $totalPendiente = $pagosPendientes->sum(fn($p) => max(0, (float) $p->importe_total - (float) $p->pagado));

        if ($importeAplicar > round($totalPendiente, 2)) {
            return response()->json([
                'error' => sprintf(
                    'El importe a pagar (%.2f €) supera el total pendiente del cliente (%.2f €).',
                    $importeAplicar,
                    $totalPendiente
                ),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $importeRestante   = $importeAplicar;
            $pagosActualizados = [];

            foreach ($pagosPendientes as $pago) {
                if ($importeRestante <= 0) {
                    break;
                }

                $pendiente = (float) $pago->importe_total - (float) $pago->pagado;

                if ($pendiente <= 0) {
                    continue;
                }

                $aplicar = min($importeRestante, $pendiente);
                $importeRestante -= $aplicar;

                DetallePagoAlquiler::create([
                    'pago_alquiler_id' => $pago->id,
                    'importe'          => $aplicar,
                    'fecha_pago'       => $fechaPago,
                    'notas'            => $notas,
                ]);

                $pago->pagado += $aplicar;
                $pago->recalcularEstado();

                $pagosActualizados[] = $pago->fresh(['detalles']);
            }

            DB::commit();

            Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

            return response()->json([
                'message'            => 'Pago registrado correctamente',
                'pagos_actualizados' => $pagosActualizados,
                'sobrante'           => round($importeRestante, 2),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al registrar el pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear manualmente un registro de pago mensual.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'    => 'required|exists:clientes,id',
            'tipo'          => 'required|in:trastero,piso',
            'referencia_id' => 'required|integer',
            'mes'           => 'required|integer|min:1|max:12',
            'anyo'          => 'required|integer|min:2000|max:2100',
            'importe_total' => 'required|numeric|min:0',
            'notas'         => 'nullable|string',
        ]);

        // Evitar duplicado
        $existe = PagoAlquiler::where('tipo', $validated['tipo'])
            ->where('referencia_id', $validated['referencia_id'])
            ->where('mes', $validated['mes'])
            ->where('anyo', $validated['anyo'])
            ->exists();

        if ($existe) {
            return response()->json(['error' => 'Ya existe un registro de pago para ese mes/año'], 422);
        }

        // Resolver numero del trastero o piso
        if ($validated['tipo'] === 'trastero') {
            $validated['numero'] = \App\Models\Trastero::find($validated['referencia_id'])?->numero;
        } else {
            $validated['numero'] = \App\Models\Piso::find($validated['referencia_id'])?->numero;
        }

        $validated['pagado'] = 0;
        $validated['estado'] = 'pendiente';

        $pago = PagoAlquiler::create($validated);

        Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

        return response()->json($pago->load(['cliente', 'detalles']), 201);
    }

    public function destroy(PagoAlquiler $pagoAlquiler): JsonResponse
    {
        $pagoAlquiler->delete();

        Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

        return response()->json(['message' => 'Registro de pago eliminado']);
    }

    /**
     * Elimina un detalle de pago y recalcula el registro principal (pagado + estado).
     */
    public function eliminarDetalle(PagoAlquiler $pagoAlquiler, DetallePagoAlquiler $detalle): JsonResponse
    {
        if ($detalle->pago_alquiler_id !== $pagoAlquiler->id) {
            return response()->json(['error' => 'El detalle no pertenece a este pago.'], 422);
        }

        DB::beginTransaction();
        try {
            $detalle->delete();

            // Recalcular pagado sumando los detalles restantes
            $pagoAlquiler->pagado = $pagoAlquiler->detalles()->sum('importe');
            $pagoAlquiler->recalcularEstado(); // guarda internamente

            DB::commit();

            Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

            return response()->json($pagoAlquiler->fresh(['cliente', 'detalles']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar el detalle: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Enviar recibo de pago por email al cliente (pago mensual o detalle).
     */
    public function enviarReciboEmail(Request $request): JsonResponse
    {
        Log::debug("message");

        //$cliente = null;
        //if (!$cliente || !$cliente->email) {
        //    return response()->json(['message' => 'El cliente no tiene email registrado 2'], 422);
        //}

        $request->validate([
            'pago_id' => 'required|exists:pagos_alquiler,id',
            'detalle_id' => 'nullable|exists:detalle_pagos_alquiler,id',
        ]);

        Log::debug("Enviando recibo de pago por email", ['pago_id' => $request->pago_id, 'detalle_id' => $request->detalle_id]);

        $pago = PagoAlquiler::with('cliente')->findOrFail($request->pago_id);
        $cliente = $pago->cliente;
        if (!$cliente || !$cliente->email) {
            return response()->json(['message' => 'El cliente no tiene email registrado'], 422);
        }

        // Si se pasa detalle_id, solo ese detalle; si no, recibo total del pago
        if ($request->filled('detalle_id')) {
            $detalle = $pago->detalles()->findOrFail($request->detalle_id);
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('emails.recibo-pago', [
                'pago' => $pago->toArray(),
                'detalle' => $detalle->toArray(),
            ]);
            $pdfData = $pdf->output();
            Mail::to($cliente->email)
                ->send(new \App\Mail\ReciboClienteMail(
                    $cliente->toArray(),
                    $pago->mes,
                    $pago->anyo,
                    $pago->toArray(),
                    $detalle->importe,
                    $pdfData,
                    $detalle->toArray()
                ));
        } else {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('emails.recibo-pago-total', [
                'pago' => $pago->toArray(),
            ]);
            $pdfData = $pdf->output();
            Mail::to($cliente->email)
                ->send(new \App\Mail\ReciboClienteMail(
                    $cliente->toArray(),
                    $pago->mes,
                    $pago->anyo,
                    $pago->toArray(),
                    $pago->importe_total,
                    $pdfData,
                    null
                ));
        }
        return response()->json(['message' => 'Recibo enviado correctamente']);
    }
}
