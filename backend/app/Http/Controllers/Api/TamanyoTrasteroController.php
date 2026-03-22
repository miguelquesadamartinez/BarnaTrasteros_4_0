<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $tamanyoTrastero->update($data);

        Cache::tags(['tamanyo-trasteros', 'trasteros'])->flush();

        return response()->json($tamanyoTrastero);
    }

    public function destroy(TamanyoTrastero $tamanyoTrastero)
    {
        $tamanyoTrastero->delete();

        Cache::tags(['tamanyo-trasteros', 'trasteros'])->flush();

        return response()->json(null, 204);
    }
}
