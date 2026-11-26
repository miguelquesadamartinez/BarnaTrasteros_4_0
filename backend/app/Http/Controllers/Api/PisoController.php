<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\DaDeBajaUnidad;
use App\Http\Controllers\Api\Concerns\GeneraPagoAlAsignar;
use App\Http\Controllers\Controller;
use App\Models\Piso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class PisoController extends Controller
{
    use DaDeBajaUnidad, GeneraPagoAlAsignar;

    public function index(Request $request): JsonResponse
    {
        $query = Piso::with('cliente');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('piso', 'like', "%{$search}%");
            });
        }

        if ($request->has('libre') && $request->libre == '1') {
            $query->whereNull('cliente_id');
        }

        return response()->json($query->orderBy('numero')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero'               => 'required|string|max:20|unique:pisos,numero',
            'piso'                 => 'required|string|max:20',
            'precio_mensual'       => 'required|numeric|min:0',
            'cliente_id'           => ['nullable', Rule::exists('clientes', 'id')->whereNull('archivado_at')],
            'fecha_inicio_alquiler'=> 'nullable|date',
            'fecha_vencimiento'    => 'nullable|date',
            'notas'                => 'nullable|string',
            'importe_final'        => 'nullable|numeric|min:0',
        ]);
        $importeFinal = $validated['importe_final'] ?? null;
        unset($validated['importe_final']);
        $validated = $this->completarFechasAlquiler($validated);

        $piso = Piso::create($validated);

        if ($piso->cliente_id !== null) {
            $this->generarPagoDelMes($piso, 'piso', $piso->cliente_id, $importeFinal);
        }

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json($piso->load('cliente'), 201);
    }

    public function show(Piso $piso): JsonResponse
    {
        $data = Cache::tags(['pisos'])->remember("pisos:show:{$piso->id}", now()->addHours(24), function () use ($piso) {
            return $piso->load('cliente');
        });

        return response()->json($data);
    }

    public function update(Request $request, Piso $piso): JsonResponse
    {
        $validated = $request->validate([
            'numero'               => "required|string|max:20|unique:pisos,numero,{$piso->id}",
            'piso'                 => 'required|string|max:20',
            'precio_mensual'       => 'required|numeric|min:0',
            'cliente_id'           => ['nullable', Rule::exists('clientes', 'id')->whereNull('archivado_at')],
            'fecha_inicio_alquiler'=> 'nullable|date',
            'fecha_vencimiento'    => 'nullable|date',
            'notas'                => 'nullable|string',
            'importe_final'        => 'nullable|numeric|min:0',
        ]);
        $importeFinal = $validated['importe_final'] ?? null;
        unset($validated['importe_final']);
        $validated = $this->completarFechasAlquiler($validated);

        if ($piso->cliente_id !== null && !empty($validated['cliente_id']) && (int) $validated['cliente_id'] !== (int) $piso->cliente_id) {
            return response()->json([
                'message' => 'Esta unidad ya está alquilada a otro cliente. Dale de baja primero.',
                'errors'  => ['cliente_id' => ['Esta unidad ya está alquilada a otro cliente. Dale de baja primero.']],
            ], 422);
        }

        $seAcabaDeAsignar = $piso->cliente_id === null && $validated['cliente_id'] !== null;

        $piso->update($validated);

        if ($seAcabaDeAsignar) {
            $this->generarPagoDelMes($piso, 'piso', $piso->cliente_id, $importeFinal);
        }

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json($piso->load('cliente'));
    }

    public function darBaja(Request $request, Piso $piso): JsonResponse
    {
        $response = $this->darDeBaja($request, $piso, 'piso');

        if ($response->getStatusCode() === 200) {
            Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();
        }

        return $response;
    }

    public function destroy(Piso $piso): JsonResponse
    {
        if ($piso->cliente_id !== null) {
            return response()->json([
                'message' => 'No se puede eliminar el piso: tiene un cliente asignado. Da de baja al cliente primero.',
            ], 422);
        }

        // Los pagos y fianzas guardan referencia_id sin clave foránea: borrar la
        // unidad los dejaría huérfanos (seguirían contando como deuda y saliendo
        // en facturas de una unidad que ya no existe).
        $tienePagos = \App\Models\PagoAlquiler::where('tipo', 'piso')->where('referencia_id', $piso->id)->exists();
        $tieneFianzas = \App\Models\Fianza::where('tipo', 'piso')->where('referencia_id', $piso->id)->exists();
        if ($tienePagos || $tieneFianzas) {
            return response()->json([
                'message' => 'No se puede eliminar el piso: tiene pagos o fianzas registrados. Conserva la unidad para mantener el historial.',
            ], 422);
        }

        $piso->delete();

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Piso eliminado correctamente']);
    }

    /**
     * Una unidad alquilada sin fecha de inicio no tiene vencimiento ni se puede
     * prorratear: si se asigna un cliente sin fechas se toma hoy como inicio y
     * un mes después como vencimiento.
     */
    private function completarFechasAlquiler(array $validated): array
    {
        if (empty($validated['cliente_id'])) {
            return $validated;
        }
        if (empty($validated['fecha_inicio_alquiler'])) {
            $validated['fecha_inicio_alquiler'] = now()->toDateString();
        }
        if (empty($validated['fecha_vencimiento'])) {
            $validated['fecha_vencimiento'] = \Carbon\Carbon::parse($validated['fecha_inicio_alquiler'])->addMonthNoOverflow()->toDateString();
        }

        return $validated;
    }
}
