<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fianza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FianzaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'fianzas:list:' . md5(serialize($request->only(['search', 'cliente_id', 'estado', 'per_page', 'page'])));

        $fianzas = Cache::tags(['fianzas'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
            $query = Fianza::with('cliente');

            $estado = $request->get('estado', 'activas');
            if ($estado === 'devueltas') {
                $query->where('devuelta', true);
            } elseif ($estado === 'activas') {
                $query->where('devuelta', false);
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->integer('cliente_id'));
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('cliente', function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                });
            }

            $perPage = $request->integer('per_page', 15);
            return $query->orderBy('fecha_entrega', 'desc')->paginate($perPage);
        });

        return response()->json($fianzas);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'tipo'               => 'nullable|in:trastero,piso',
            'referencia_id'      => 'nullable|integer',
            'numero'             => 'nullable|string|max:20',
            'importe'            => 'required|numeric|min:0',
            'fecha_entrega'      => 'required|date',
            'devuelta'           => 'nullable|boolean',
            'fecha_devolucion'   => 'nullable|date',
            'notas'              => 'nullable|string',
        ]);

        $validated['devuelta'] = $request->boolean('devuelta');

        $fianza = Fianza::create($validated);

        Cache::tags(['fianzas'])->flush();

        return response()->json($fianza->load('cliente'), 201);
    }

    public function show(Fianza $fianza): JsonResponse
    {
        return response()->json($fianza->load('cliente'));
    }

    public function update(Request $request, Fianza $fianza): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'tipo'               => 'nullable|in:trastero,piso',
            'referencia_id'      => 'nullable|integer',
            'numero'             => 'nullable|string|max:20',
            'importe'            => 'required|numeric|min:0',
            'fecha_entrega'      => 'required|date',
            'devuelta'           => 'nullable|boolean',
            'fecha_devolucion'   => 'nullable|date',
            'notas'              => 'nullable|string',
        ]);

        $validated['devuelta'] = $request->boolean('devuelta');

        if ($validated['devuelta'] && empty($validated['fecha_devolucion'])) {
            $validated['fecha_devolucion'] = now()->toDateString();
        }
        if (!$validated['devuelta']) {
            $validated['fecha_devolucion'] = null;
        }

        $fianza->update($validated);

        Cache::tags(['fianzas'])->flush();

        return response()->json($fianza->load('cliente'));
    }

    public function destroy(Fianza $fianza): JsonResponse
    {
        $fianza->delete();

        Cache::tags(['fianzas'])->flush();

        return response()->json(['message' => 'Fianza eliminada correctamente']);
    }
}
