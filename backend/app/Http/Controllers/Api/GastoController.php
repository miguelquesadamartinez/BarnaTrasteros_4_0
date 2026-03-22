<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleGasto;
use App\Models\Gasto;
use App\Models\ImagenGasto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GastoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'gastos:list:' . md5(serialize($request->only(['tipo', 'estado', 'per_page', 'page'])));

        $gastos = Cache::tags(['gastos'])->remember($cacheKey, now()->addHours(24), function () use ($request) {
            $query = Gasto::with(['detalles', 'imagenes']);

            if ($request->has('tipo') && $request->tipo) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('estado') && $request->estado) {
                $query->where('estado', $request->estado);
            }

            $perPage = $request->integer('per_page', 15);
            $result = $query->orderBy('fecha_emision', 'desc')->paginate($perPage);

            $result->getCollection()->each(function ($gasto) {
                $gasto->imagenes->each(function ($imagen) {
                    $imagen->url = url('storage/' . $imagen->ruta);
                });
            });

            return $result;
        });

        return response()->json($gastos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo'              => 'required|in:agua,luz,comunidad,mantenimiento,otro',
            'descripcion'       => 'required|string|max:200',
            'referencia_tipo'   => 'nullable|in:piso,trastero,general',
            'referencia_id'     => 'nullable|integer',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'importe_total'     => 'required|numeric|min:0',
            'notas'             => 'nullable|string',
            'imagenes.*'        => 'nullable|image|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $validated['pagado'] = 0;
        $validated['estado'] = 'pendiente';
        $validated['referencia_tipo'] = $validated['referencia_tipo'] ?? 'general';

        DB::beginTransaction();
        try {
            $gasto = Gasto::create($validated);

            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $file) {
                    $path = $file->store("gastos/{$gasto->id}", 'public');
                    ImagenGasto::create([
                        'gasto_id'        => $gasto->id,
                        'ruta'            => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear el gasto: ' . $e->getMessage()], 500);
        }

        Cache::tags(['gastos', 'relatorio'])->flush();

        $gasto->load(['detalles', 'imagenes']);
        $gasto->imagenes->each(function ($imagen) {
            $imagen->url = url('storage/' . $imagen->ruta);
        });

        return response()->json($gasto, 201);
    }

    public function show(Gasto $gasto): JsonResponse
    {
        $data = Cache::tags(['gastos'])->remember("gastos:show:{$gasto->id}", now()->addHours(24), function () use ($gasto) {
            $gasto->load(['detalles', 'imagenes']);
            $gasto->imagenes->each(function ($imagen) {
                $imagen->url = url('storage/' . $imagen->ruta);
            });
            return $gasto;
        });

        return response()->json($data);
    }

    public function update(Request $request, Gasto $gasto): JsonResponse
    {
        $validated = $request->validate([
            'tipo'              => 'required|in:agua,luz,comunidad,mantenimiento,otro',
            'descripcion'       => 'required|string|max:200',
            'referencia_tipo'   => 'nullable|in:piso,trastero,general',
            'referencia_id'     => 'nullable|integer',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'importe_total'     => 'required|numeric|min:0',
            'notas'             => 'nullable|string',
        ]);

        $gasto->update($validated);
        $gasto->recalcularEstado();

        Cache::tags(['gastos', 'relatorio'])->flush();

        $gasto->load(['detalles', 'imagenes']);
        $gasto->imagenes->each(function ($imagen) {
            $imagen->url = url('storage/' . $imagen->ruta);
        });

        return response()->json($gasto);
    }

    public function destroy(Gasto $gasto): JsonResponse
    {
        // Eliminar imágenes del storage
        foreach ($gasto->imagenes as $imagen) {
            Storage::disk('public')->delete($imagen->ruta);
        }
        $gasto->delete();

        Cache::tags(['gastos', 'relatorio'])->flush();

        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }

    /**
     * Registrar un pago parcial o total de un gasto.
     */
    public function registrarPago(Request $request, Gasto $gasto): JsonResponse
    {
        $validated = $request->validate([
            'importe'    => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
            'notas'      => 'nullable|string',
        ]);

        $pendiente = round((float) $gasto->importe_total - (float) $gasto->pagado, 2);

        if (round((float) $validated['importe'], 2) > $pendiente) {
            return response()->json([
                'error' => sprintf(
                    'El importe a pagar (%.2f €) supera el pendiente del gasto (%.2f €).',
                    $validated['importe'],
                    $pendiente
                ),
            ], 422);
        }

        DB::beginTransaction();
        try {
            DetalleGasto::create([
                'gasto_id'   => $gasto->id,
                'importe'    => $validated['importe'],
                'fecha_pago' => $validated['fecha_pago'],
                'notas'      => $validated['notas'] ?? null,
            ]);

            $gasto->pagado += (float) $validated['importe'];
            $gasto->recalcularEstado();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al registrar el pago: ' . $e->getMessage()], 500);
        }

        Cache::tags(['gastos', 'relatorio'])->flush();

        $gasto->load(['detalles', 'imagenes']);
        $gasto->imagenes->each(function ($imagen) {
            $imagen->url = url('storage/' . $imagen->ruta);
        });

        return response()->json($gasto);
    }

    /**
     * Subir imágenes adicionales a un gasto existente.
     */
    public function subirImagenes(Request $request, Gasto $gasto): JsonResponse
    {
        $request->validate([
            'imagenes'   => 'required|array',
            'imagenes.*' => 'image|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $nuevas = [];
        foreach ($request->file('imagenes') as $file) {
            $path = $file->store("gastos/{$gasto->id}", 'public');
            $img = ImagenGasto::create([
                'gasto_id'        => $gasto->id,
                'ruta'            => $path,
                'nombre_original' => $file->getClientOriginalName(),
            ]);
            $img->url = url('storage/' . $img->ruta);
            $nuevas[] = $img;
        }

        Cache::tags(['gastos'])->flush();

        return response()->json(['imagenes' => $nuevas]);
    }

    /**
     * Eliminar una imagen de un gasto.
     */
    public function eliminarImagen(Gasto $gasto, ImagenGasto $imagen): JsonResponse
    {
        Storage::disk('public')->delete($imagen->ruta);
        $imagen->delete();

        Cache::tags(['gastos'])->flush();

        return response()->json(['message' => 'Imagen eliminada']);
    }

    /**
     * Elimina un detalle (pago realizado) de un gasto y recalcula pagado + estado.
     */
    public function eliminarDetalle(Gasto $gasto, DetalleGasto $detalle): JsonResponse
    {
        if ($detalle->gasto_id !== $gasto->id) {
            return response()->json(['error' => 'El detalle no pertenece a este gasto.'], 422);
        }

        DB::beginTransaction();
        try {
            $detalle->delete();

            $gasto->pagado = $gasto->detalles()->sum('importe');
            $gasto->recalcularEstado(); // guarda internamente

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar el detalle: ' . $e->getMessage()], 500);
        }

        Cache::tags(['gastos', 'relatorio'])->flush();

        $gasto->load(['detalles', 'imagenes']);
        $gasto->imagenes->each(function ($imagen) {
            $imagen->url = url('storage/' . $imagen->ruta);
        });

        return response()->json($gasto);
    }
}
