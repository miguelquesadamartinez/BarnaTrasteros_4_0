<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trastero;
use App\Models\TamanyoTrastero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TamanyoTrasteroController extends Controller
{
    public function index()
    {
        return Cache::tags(['tamanyo-trasteros'])->remember('tamanyo-trasteros:all', now()->addHours(24), function () {
            return TamanyoTrastero::orderBy('orden')->orderBy('nombre')->get();
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tamanyo_trasteros,nombre',
            'descripcion' => 'nullable|string|max:255',
            'orden'       => 'nullable|integer|min:0',
            'activo'      => 'nullable|boolean',
        ]);

        $tamanyo = TamanyoTrastero::create($data);

        Cache::tags(['tamanyo-trasteros', 'trasteros'])->flush();

        return response()->json($tamanyo, 201);
    }

    public function update(Request $request, TamanyoTrastero $tamanyoTrastero)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tamanyo_trasteros,nombre,' . $tamanyoTrastero->id,
            'descripcion' => 'nullable|string|max:255',
            'orden'       => 'nullable|integer|min:0',
            'activo'      => 'nullable|boolean',
        ]);

        $nombreAnterior = $tamanyoTrastero->nombre;

        $tamanyoTrastero->update($data);

        // trasteros.tamanyo es una copia de texto libre (no una FK), así que un
        // renombrado del catálogo no se reflejaba en los trasteros que ya lo
        // usaban, dejándolos con un valor huérfano. Se propaga aquí el cambio.
        if ($data['nombre'] !== $nombreAnterior) {
            Trastero::where('tamanyo', $nombreAnterior)->update(['tamanyo' => $data['nombre']]);
        }

        Cache::tags(['tamanyo-trasteros', 'trasteros'])->flush();

        return response()->json($tamanyoTrastero);
    }

    public function destroy(TamanyoTrastero $tamanyoTrastero)
    {
        if (Trastero::where('tamanyo', $tamanyoTrastero->nombre)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: hay trasteros usando este tamaño. Cambia su tamaño primero o renombra este en vez de borrarlo.',
            ], 422);
        }

        $tamanyoTrastero->delete();

        Cache::tags(['tamanyo-trasteros', 'trasteros'])->flush();

        return response()->json(null, 204);
    }
}
