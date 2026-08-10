<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Fianza;
use App\Models\Gasto;
use App\Models\PagoAlquiler;
use App\Models\Piso;
use App\Models\Trastero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'clientes' => [],
                'trasteros' => [],
                'pisos' => [],
                'fianzas' => [],
                'gastos' => [],
                'pagos' => [],
            ]);
        }

        $clientes = Cliente::where('nombre', 'like', "%{$q}%")
            ->orWhere('apellido', 'like', "%{$q}%")
            ->orWhere('dni', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('telefono', 'like', "%{$q}%")
            ->limit(8)
            ->get(['id', 'nombre', 'apellido', 'dni', 'telefono']);

        $trasteros = Trastero::with('cliente:id,nombre,apellido')
            ->where('numero', 'like', "%{$q}%")
            ->orWhere('piso', 'like', "%{$q}%")
            ->orWhere('tamanyo', 'like', "%{$q}%")
            ->orWhere('notas', 'like', "%{$q}%")
            ->limit(8)
            ->get();

        $pisos = Piso::with('cliente:id,nombre,apellido')
            ->where('numero', 'like', "%{$q}%")
            ->orWhere('piso', 'like', "%{$q}%")
            ->orWhere('notas', 'like', "%{$q}%")
            ->limit(8)
            ->get();

        $fianzas = Fianza::with('cliente:id,nombre,apellido')
            ->where('numero', 'like', "%{$q}%")
            ->orWhere('notas', 'like', "%{$q}%")
            ->limit(8)
            ->get();

        $gastos = Gasto::where('descripcion', 'like', "%{$q}%")
            ->orWhere('tipo', 'like', "%{$q}%")
            ->orWhere('notas', 'like', "%{$q}%")
            ->limit(8)
            ->get();

        $pagos = PagoAlquiler::with('cliente:id,nombre,apellido')
            ->where('numero', 'like', "%{$q}%")
            ->orWhere('notas', 'like', "%{$q}%")
            ->limit(8)
            ->get();

        return response()->json([
            'clientes' => $clientes,
            'trasteros' => $trasteros,
            'pisos' => $pisos,
            'fianzas' => $fianzas,
            'gastos' => $gastos,
            'pagos' => $pagos,
        ]);
    }
}
