<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Fianza;
use App\Models\PagoAlquiler;
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
            $query = Cliente::with(['trasteros', 'pisos'])
                ->withExists(['pagosAlquiler as tiene_pagos', 'fianzas as tiene_fianzas'])
                ->whereNull('archivado_at');

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
            return Cliente::with(['trasteros', 'pisos'])->whereNull('archivado_at')->orderBy('apellido')->orderBy('nombre')->get();
        });

        return response()->json($clientes);
    }

    public function archivados(Request $request): JsonResponse
    {
        $cacheKey = 'clientes:archivados:' . md5(serialize($request->only(['search', 'per_page', 'page'])));

        $clientes = Cache::tags(['clientes'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
            $query = Cliente::with(['trasteros', 'pisos'])->whereNotNull('archivado_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                });
            }

            $perPage = $request->integer('per_page', 15);
            return $query->orderByDesc('archivado_at')->paginate($perPage);
        });

        return response()->json($clientes);
    }

    public function archivar(Request $request, Cliente $cliente): JsonResponse
    {
        if ($cliente->archivado_at !== null) {
            return response()->json(['message' => 'Este cliente ya está archivado.'], 422);
        }

        $unidades = collect()
            ->concat($cliente->trasteros->map(fn ($t) => ['tipo' => 'trastero', 'modelo' => $t]))
            ->concat($cliente->pisos->map(fn ($p) => ['tipo' => 'piso', 'modelo' => $p]));

        if ($unidades->isNotEmpty() && !$request->boolean('force')) {
            $pagosPendientes = collect();
            $fianzasPendientes = collect();

            foreach ($unidades as $u) {
                $pagosPendientes = $pagosPendientes->concat(
                    PagoAlquiler::where('tipo', $u['tipo'])->where('referencia_id', $u['modelo']->id)
                        ->whereIn('estado', ['pendiente', 'parcial'])
                        ->get(['id', 'numero', 'mes', 'anyo', 'importe_total', 'pagado', 'estado'])
                );
                $fianzasPendientes = $fianzasPendientes->concat(
                    Fianza::where('tipo', $u['tipo'])->where('referencia_id', $u['modelo']->id)
                        ->where('devuelta', false)
                        ->get(['id', 'numero', 'importe', 'fecha_entrega'])
                );
            }

            if ($pagosPendientes->isNotEmpty() || $fianzasPendientes->isNotEmpty()) {
                return response()->json([
                    'requiere_confirmacion' => true,
                    'unidades' => $unidades->pluck('modelo.numero')->values(),
                    'pagos_pendientes' => $pagosPendientes->values(),
                    'fianzas_pendientes' => $fianzasPendientes->values(),
                ], 409);
            }
        }

        $unidadesLiberadas = [];
        foreach ($unidades as $u) {
            $modelo = $u['modelo'];
            $nota = 'Baja automática al archivar a ' . $cliente->nombre_completo . ' el ' . now()->format('d/m/Y') . '.';
            $modelo->notas = trim(($modelo->notas ? $modelo->notas . "\n" : '') . $nota);
            $modelo->cliente_id = null;
            $modelo->fecha_inicio_alquiler = null;
            $modelo->fecha_vencimiento = null;
            $modelo->save();
            $unidadesLiberadas[] = $modelo->numero;
        }

        $cliente->archivado_at = now();
        $cliente->save();

        Cache::tags(['clientes', 'trasteros', 'pisos', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json([
            'cliente' => $cliente->fresh(),
            'unidades_liberadas' => $unidadesLiberadas,
        ]);
    }

    public function desarchivar(Cliente $cliente): JsonResponse
    {
        if ($cliente->archivado_at === null) {
            return response()->json(['message' => 'Este cliente no está archivado.'], 422);
        }

        $cliente->archivado_at = null;
        $cliente->save();

        Cache::tags(['clientes'])->flush();

        return response()->json($cliente);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:20',
            'dni'              => 'required|string|max:20|unique:clientes,dni',
            'email'            => 'nullable|email|max:150',
            'foto_dni'         => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
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
            'foto_dni'         => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
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
        if ($cliente->pagosAlquiler()->exists() || $cliente->fianzas()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el cliente tiene pagos o fianzas registradas. Usa "Archivar" en su lugar para conservar el historial.',
            ], 422);
        }

        if ($cliente->foto_dni) {
            Storage::disk('public')->delete($cliente->foto_dni);
        }
        $cliente->delete();

        Cache::tags(['clientes', 'trasteros', 'pisos', 'relatorio', 'facturas', 'pagos-alquiler'])->flush();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    public function generarContrato(Cliente $cliente): JsonResponse
    {
        $cliente->load(['trasteros', 'pisos', 'fianzas']);

        if ($cliente->trasteros->isEmpty() && $cliente->pisos->isEmpty()) {
            return response()->json(['message' => 'El cliente no tiene ninguna unidad asignada'], 422);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('contratos.alquiler', [
            'cliente' => $cliente->toArray(),
            'trasteros' => $cliente->trasteros->toArray(),
            'pisos' => $cliente->pisos->toArray(),
            'fianzas' => $cliente->fianzas->where('devuelta', false)->values()->toArray(),
            'empresa' => config('empresa'),
        ]);
        $pdfData = $pdf->output();

        if ($cliente->contrato_path) {
            Storage::disk('public')->delete($cliente->contrato_path);
        }

        $path = "clientes/contratos/cliente-{$cliente->id}-" . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->put($path, $pdfData);

        $cliente->update(['contrato_path' => $path]);

        Cache::tags(['clientes'])->flush();

        return response()->json(['path' => $path]);
    }

    public function avisarImpago(Cliente $cliente): JsonResponse
    {
        if (!$cliente->enviarAvisoImpago()) {
            return response()->json(['message' => 'El cliente no tiene pagos pendientes o no tiene email registrado'], 422);
        }

        return response()->json(['message' => 'Aviso enviado']);
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
