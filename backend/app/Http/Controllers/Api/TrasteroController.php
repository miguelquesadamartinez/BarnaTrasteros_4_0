<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrasteroController extends Controller
{
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

        $trasteros = $query->orderBy('numero')->get();

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

        return response()->json($trastero->load('cliente'), 201);
    }

    public function show(Trastero $trastero): JsonResponse
    {
        return response()->json($trastero->load('cliente'));
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

        return response()->json($trastero->load('cliente'));
    }

    public function destroy(Trastero $trastero): JsonResponse
    {
        $trastero->delete();

        return response()->json(['message' => 'Trastero eliminado correctamente']);
    }
}
