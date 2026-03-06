<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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

        $perPage = (int) $request->get('per_page', 15);
        $clientes = $query->orderBy('apellido')->orderBy('nombre')->paginate($perPage);

        return response()->json($clientes);
    }

    public function listAll(Request $request): JsonResponse
    {
        $query = Cliente::with(['trasteros', 'pisos']);
        $clientes = $query->orderBy('apellido')->orderBy('nombre')->get();
        return response()->json($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'dni'              => 'required|string|max:20|unique:clientes,dni',
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

        return response()->json($cliente->load(['trasteros', 'pisos']), 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente->load(['trasteros', 'pisos']));
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'dni'              => ['required', 'string', 'max:20', Rule::unique('clientes', 'dni')->ignore($cliente->id)],
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

        return response()->json($cliente->load(['trasteros', 'pisos']));
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        if ($cliente->foto_dni) {
            Storage::disk('public')->delete($cliente->foto_dni);
        }
        $cliente->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }
}
