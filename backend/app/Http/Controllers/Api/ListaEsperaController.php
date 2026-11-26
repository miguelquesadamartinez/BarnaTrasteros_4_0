<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use Illuminate\Http\JsonResponse;
use App\Support\DatosContacto;
use Illuminate\Http\Request;

class ListaEsperaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ListaEspera::orderBy('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        DatosContacto::normalizar($request);
        $data = $request->validate([
            'nombre'   => 'required|string|max:150',
            'telefono' => ['required', 'string', 'max:20', 'regex:' . DatosContacto::REGEX_TELEFONO],
            'tamanyo'  => 'required|string|max:100',
        ], DatosContacto::MENSAJES);

        $entrada = ListaEspera::create($data);

        return response()->json($entrada, 201);
    }

    public function destroy(ListaEspera $listaEspera): JsonResponse
    {
        $listaEspera->delete();

        return response()->json(null, 204);
    }
}
