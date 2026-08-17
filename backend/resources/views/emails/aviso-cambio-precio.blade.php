<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualización de precio</title>
</head>
<body style="font-family: Arial, sans-serif; background: #ffffff; color: #222; margin:0; padding:0;">
@php
    $logoSrc = null;
    if (isset($message) && file_exists(public_path('logo.jpg'))) {
        $logoSrc = $message->embed(public_path('logo.jpg'));
    } elseif (file_exists(public_path('logo.jpg'))) {
        $logoSrc = public_path('logo.jpg');
    }
@endphp
<div style="max-width:800px;margin:0 auto;background:#fff;">
    <div style="background:#fcc105;padding:18px 24px 10px 24px;display:flex;align-items:center;">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" style="height:48px;margin-right:24px;" alt="Logo" />
        @endif
        <div style="flex:1;">
            <h2 style="margin:0;color:#111827;font-size:1.8rem;letter-spacing:1px;">
                {{ count($filas) > 1 ? 'ACTUALIZACIÓN DE PRECIOS' : 'ACTUALIZACIÓN DE PRECIO' }}
            </h2>
        </div>
    </div>
    <div style="padding:28px 32px 18px 32px;">
        <p style="font-size:1.05rem;">Estimado/a <strong>{{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</strong>,</p>
        <p style="font-size:1.02rem;">
            Le informamos de que, a partir de la próxima mensualidad, el precio de
            {{ count($filas) > 1 ? 'los siguientes alquileres se actualiza' : 'su alquiler se actualiza' }}:
        </p>

        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <thead>
                <tr>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:left;border:1px solid #e5e7eb;">Unidad</th>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:right;border:1px solid #e5e7eb;">Precio anterior</th>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:right;border:1px solid #e5e7eb;">Precio nuevo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $i => $f)
                    <tr @if($i % 2 === 0) style="background:#f8f8f8;" @endif>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ $f->tipo === 'piso' ? 'Piso' : 'Trastero' }} {{ $f->numero ?? $f->referencia_id }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;text-align:right;border:1px solid #e5e7eb;">
                            {{ number_format($f->precio_anterior, 2, ',', '.') }} €
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#111827;font-weight:bold;text-align:right;border:1px solid #e5e7eb;">
                            {{ number_format($f->precio_nuevo, 2, ',', '.') }} €
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size:1.02rem;margin-top:24px;">
            Si tiene cualquier duda sobre este cambio, no dude en ponerse en contacto con nosotros.
        </p>

        <div style="margin-top:32px;font-size:.95rem;color:#444;">
            <div><strong>{{ $empresa['nombre'] }}</strong></div>
            @if(!empty($empresa['direccion']))<div>{{ $empresa['direccion'] }}</div>@endif
            @if(!empty($empresa['telefono']))<div>Tel: {{ $empresa['telefono'] }}</div>@endif
        </div>

        <div style="margin-top:24px;font-size:1.05rem;color:#222;">Un cordial saludo,<br><strong>{{ $empresa['responsable'] ?: $empresa['nombre'] }}</strong><br><span style="color:#888">{{ $empresa['nombre'] }}</span></div>
    </div>
</div>
</body>
</html>
