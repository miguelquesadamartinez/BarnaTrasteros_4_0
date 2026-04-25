<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'clientes:list:' . md5(serialize($request->only(['search', 'per_page', 'page'])));

        $clientes = Cache::tags(['clientes'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
            $query = Cliente::with(['trasteros', 'pisos']);

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%")
                      ->orWhere('telefono', 'like', "%{$search}%");
                });
            }

            $perPage = $request->integer('per_page', 15);
            return $query->orderBy('apellido')->orderBy('nombre')->paginate($perPage);
        });

        return response()->json($clientes);
    }

    public function listAll(Request $request): JsonResponse
    {
        $clientes = Cache::tags(['clientes'])->remember('clientes:all', now()->addHours(24), function () {
            return Cliente::with(['trasteros', 'pisos'])->orderBy('apellido')->orderBy('nombre')->get();
        });

        return response()->json($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'dni'              => 'required|string|max:20|unique:clientes,dni',
            'email'            => 'nullable|email|max:150',
            'foto_dni'         => 'nullable|image|mimes:jpg,jpeg,png,pdf|max:5120',
            'direccion'        => 'nullable|string|max:200',
            'codigo_postal'    => 'nullable|string|max:10',
            'ciudad'           => 'nullable|string|max:100',
            'necesita_factura' => 'nullable|boolean',
        ]);

        $validated['necesita_factura'] = $request->boolean('necesita_factura');

        if ($request->hasFile('foto_dni')) {
            $path = $request->file('foto_dni')->store('clientes/dni', 'public');
            $validated['foto_dni'] = $path;
        }

        $cliente = Cliente::create($validated);

        Cache::tags(['clientes'])->flush();

        return response()->json($cliente->load(['trasteros', 'pisos']), 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        $data = Cache::tags(['clientes'])->remember("clientes:show:{$cliente->id}", now()->addHours(24), function () use ($cliente) {
            return $cliente->load(['trasteros', 'pisos']);
        });

        return response()->json($data);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'dni'              => ['required', 'string', 'max:20', Rule::unique('clientes', 'dni')->ignore($cliente->id)],
            'email'            => 'nullable|email|max:150',
            'foto_dni'         => 'nullable|image|mimes:jpg,jpeg,png,pdf|max:5120',
            'direccion'        => 'nullable|string|max:200',
            'codigo_postal'    => 'nullable|string|max:10',
            'ciudad'           => 'nullable|string|max:100',
            'necesita_factura' => 'nullable|boolean',
        ]);

        $validated['necesita_factura'] = $request->boolean('necesita_factura');

        if ($request->hasFile('foto_dni')) {
            // Eliminar foto anterior si existe
            if ($cliente->foto_dni) {
                Storage::disk('public')->delete($cliente->foto_dni);
            }
            $path = $request->file('foto_dni')->store('clientes/dni', 'public');
            $validated['foto_dni'] = $path;
        }

        $cliente->update($validated);

        Cache::tags(['clientes', 'trasteros', 'pisos', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json($cliente->load(['trasteros', 'pisos']));
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        if ($cliente->foto_dni) {
            Storage::disk('public')->delete($cliente->foto_dni);
        }
        $cliente->delete();

        Cache::tags(['clientes', 'trasteros', 'pisos', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    public function pendienteTotal(Request $request, int $id): JsonResponse
    {
        $pendiente = Cache::tags(['clientes', 'pagos-alquiler'])->remember("clientes:pendiente:{$id}", now()->addMinutes(10), function () use ($id) {
            $cliente = Cliente::findOrFail($id);
            return round(
                $cliente->pagosAlquiler()
                    ->whereIn('estado', ['pendiente', 'parcial'])
                    ->get()
                    ->reduce(fn ($carry, $pago) => $carry + max(0, $pago->importe_total - $pago->pagado), 0),
                2
            );
        });

        return response()->json(['pendiente_total' => $pendiente]);
    }
}
