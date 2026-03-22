<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrasteroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'trasteros:list:' . md5(serialize($request->only(['search', 'libre'])));

        $trasteros = Cache::tags(['trasteros'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
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

            return $query->orderBy('numero')->get();
        });

        return response()->json($trasteros);
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

    public function destroy(Trastero $trastero): JsonResponse
    {
        $trastero->delete();

        Cache::tags(['trasteros', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Trastero eliminado correctamente']);
    }
}
