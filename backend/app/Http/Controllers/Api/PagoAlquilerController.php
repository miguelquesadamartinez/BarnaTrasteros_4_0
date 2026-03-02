<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetallePagoAlquiler;
use App\Models\PagoAlquiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoAlquilerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
            $query->where('estado', $request->estado);
        }

        if ($request->has('anyo') && $request->anyo) {
            $query->where('anyo', $request->anyo);
        }

        if ($request->has('mes') && $request->mes) {
            $query->where('mes', $request->mes);
        }

        $perPage = (int) $request->get('per_page', 15);
        $pagos = $query->orderBy('anyo', 'desc')->orderBy('mes', 'desc')->paginate($perPage);

        return response()->json($pagos);
    }

    public function show(PagoAlquiler $pagoAlquiler): JsonResponse
    {
        return response()->json($pagoAlquiler->load(['cliente', 'detalles']));
    }

    /**
     * Registrar un pago - distribuye automáticamente entre meses pendientes del mismo tipo/referencia.
     * El pago se aplica primero al mes más antiguo con saldo pendiente.
     */
    public function registrarPago(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo'         => 'required|in:trastero,piso',
            'referencia_id'=> 'required|integer',
            'importe'      => 'required|numeric|min:0.01',
            'fecha_pago'   => 'required|date',
            'notas'        => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $tipo          = $validated['tipo'];
            $referenciaId  = $validated['referencia_id'];
            $importeRestante = (float) $validated['importe'];
            $fechaPago     = $validated['fecha_pago'];
            $notas         = $validated['notas'] ?? null;

            // Obtener todos los pagos pendientes o parciales, ordenados del más antiguo al más reciente
            $pagosPendientes = PagoAlquiler::where('tipo', $tipo)
                ->where('referencia_id', $referenciaId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderBy('anyo')
                ->orderBy('mes')
                ->get();

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

                // Crear el detalle
                DetallePagoAlquiler::create([
                    'pago_alquiler_id' => $pago->id,
                    'importe'          => $aplicar,
                    'fecha_pago'       => $fechaPago,
                    'notas'            => $notas,
                ]);

                // Actualizar el pago principal
                $pago->pagado += $aplicar;
                $pago->recalcularEstado();

                $pagosActualizados[] = $pago->fresh(['detalles']);
            }

            // Si queda importe sin aplicar y no hay más meses pendientes, se descarta o se guarda
            // Aquí simplemente informamos del sobrante
            DB::commit();

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

        $validated['pagado'] = 0;
        $validated['estado'] = 'pendiente';

        $pago = PagoAlquiler::create($validated);

        return response()->json($pago->load(['cliente', 'detalles']), 201);
    }

    public function destroy(PagoAlquiler $pagoAlquiler): JsonResponse
    {
        $pagoAlquiler->delete();

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

            return response()->json($pagoAlquiler->fresh(['cliente', 'detalles']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar el detalle: ' . $e->getMessage()], 500);
        }
    }
}
