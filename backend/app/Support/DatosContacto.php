<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Normalización y validación de formato de los datos de contacto que se
 * escriben a mano (DNI/NIE/CIF, teléfono, código postal). Solo se comprueba
 * el formato, no la letra de control.
 */
class DatosContacto
{
    public const REGEX_DOCUMENTO = '/^(\d{8}[A-Z]|[XYZ]\d{7}[A-Z]|[ABCDEFGHJNPQRSUVW]\d{7}[0-9A-J])$/';
    public const REGEX_TELEFONO = '/^\+?\d{9,15}$/';
    public const REGEX_CODIGO_POSTAL = '/^\d{5}$/';

    public const MENSAJES = [
        'dni.regex'           => 'El DNI/NIE/CIF no tiene un formato válido (ej.: 12345678A, X1234567B o B12345678).',
        'telefono.regex'      => 'El teléfono debe tener 9 cifras (o empezar por + y el prefijo del país).',
        'codigo_postal.regex' => 'El código postal debe tener 5 cifras.',
    ];

    /** Quita espacios, guiones, puntos y paréntesis y pasa el documento a mayúsculas. */
    public static function normalizar(Request $request): void
    {
        $limpio = [];
        if ($request->filled('dni')) {
            $limpio['dni'] = strtoupper(preg_replace('/[\s.\-]/', '', (string) $request->input('dni')));
        }
        if ($request->filled('telefono')) {
            $limpio['telefono'] = preg_replace('/[\s.\-()]/', '', (string) $request->input('telefono'));
        }
        if ($request->filled('codigo_postal')) {
            $limpio['codigo_postal'] = preg_replace('/\s/', '', (string) $request->input('codigo_postal'));
        }
        $request->merge($limpio);
    }
}
