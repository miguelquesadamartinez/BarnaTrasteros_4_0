<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\DaDeBajaUnidad;
use App\Http\Controllers\Controller;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrasteroController extends Controller
{
    use DaDeBajaUnidad;

    public function index(Request $request): JsonResponse
    {
        $query = Trastero::with('cliente');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('piso', 'like', "%{$search}%")
                  ->orWhere('tamanyo', 'like', "%{$search}%");
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
            'numero'               => 'required|string|max:20|unique:trasteros,numero',
            'piso'                 => 'required|string|max:20',
            'tamanyo'              => 'required|string|max:50',
            'precio_mensual'       => 'required|numeric|min:0',
            'cliente_id'           => 'nullable|exists:clientes,id',
            'fecha_inicio_alquiler'=> 'nullable|date',
            'notas'                => 'nullable|string',
        ]);

        $trastero = Trastero::create($validated);

        Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas'])->flush();

        return response()->json($trastero->load('cliente'), 201);
    }

    public function show(Trastero $trastero): JsonResponse
    {
        $data = Cache::tags(['trasteros'])->remember("trasteros:show:{$trastero->id}", now()->addHours(24), function () use ($trastero) {
            return $trastero->load('cliente');
        });

        return response()->json($data);
    }

    public function update(Request $request, Trastero $trastero): JsonResponse
    {
        $validated = $request->validate([
            'numero'               => "required|string|max:20|unique:trasteros,numero,{$trastero->id}",
            'piso'                 => 'required|string|max:20',
            'tamanyo'              => 'required|string|max:50',
            'precio_mensual'       => 'required|numeric|min:0',
            'cliente_id'           => 'nullable|exists:clientes,id',
            'fecha_inicio_alquiler'=> 'nullable|date',
            'notas'                => 'nullable|string',
        ]);

        $trastero->update($validated);

        Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json($trastero->load('cliente'));
    }

    public function darBaja(Request $request, Trastero $trastero): JsonResponse
    {
        $response = $this->darDeBaja($request, $trastero, 'trastero');

        if ($response->getStatusCode() === 200) {
            Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();
        }

        return $response;
    }

    public function destroy(Trastero $trastero): JsonResponse
    {
        if ($trastero->cliente_id !== null) {
            return response()->json([
                'message' => 'No se puede eliminar el trastero: tiene un cliente asignado. Da de baja al cliente primero.',
            ], 422);
        }

        $trastero->delete();

        Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Trastero eliminado correctamente']);
    }
}
