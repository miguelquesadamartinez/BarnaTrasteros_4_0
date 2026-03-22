<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Piso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PisoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'pisos:list:' . md5(serialize($request->only(['search', 'libre'])));

        $pisos = Cache::tags(['pisos'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
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

            return $query->orderBy('numero')->get();
        });

        return response()->json($pisos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero'               => 'required|string|max:20|unique:pisos,numero',
            'piso'                 => 'required|string|max:20',
            'precio_mensual'       => 'required|numeric|min:0',
            'cliente_id'           => 'nullable|exists:clientes,id',
            'fecha_inicio_alquiler'=> 'nullable|date',
            'notas'                => 'nullable|string',
        ]);

        $piso = Piso::create($validated);

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas'])->flush();

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
            'cliente_id'           => 'nullable|exists:clientes,id',
            'fecha_inicio_alquiler'=> 'nullable|date',
            'notas'                => 'nullable|string',
        ]);

        $piso->update($validated);

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json($piso->load('cliente'));
    }

    public function destroy(Piso $piso): JsonResponse
    {
        $piso->delete();

        Cache::tags(['pisos', 'clientes', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Piso eliminado correctamente']);
    }
}
