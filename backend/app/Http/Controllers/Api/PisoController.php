<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Piso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PisoController extends Controller
{
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

        $pisos = $query->orderBy('numero')->get();

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

        return response()->json($piso->load('cliente'), 201);
    }

    public function show(Piso $piso): JsonResponse
    {
        return response()->json($piso->load('cliente'));
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

        return response()->json($piso->load('cliente'));
    }

    public function destroy(Piso $piso): JsonResponse
    {
        $piso->delete();

        return response()->json(['message' => 'Piso eliminado correctamente']);
    }
}
