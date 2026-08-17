<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de precio</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#1f2937;">
@php
    $logoCid = null;
    if (isset($message) && file_exists(public_path('logo.jpg'))) {
        $logoCid = $message->embed(public_path('logo.jpg'));
    }
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:20px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="760" cellpadding="0" cellspacing="0" style="width:760px;max-width:95%;background:#ffffff;border-collapse:collapse;">
                <tr>
                    <td style="height:6px;background:#fcc105;"></td>
                </tr>

                <tr>
                    <td style="background:#f8f8f8;padding:14px 18px 16px 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <tr>
                                <td valign="top" style="width:60%;padding-right:10px;">
                                    @if($logoCid)
                                        <img src="{{ $logoCid }}" alt="{{ $empresa['nombre'] }}" style="display:block;width:140px;max-width:100%;height:auto;margin-bottom:8px;">
                                    @endif
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">{{ $empresa['direccion'] }}</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">{{ $empresa['responsable'] }}</div>
                                </td>
                                <td valign="top" align="right" style="width:40%;">
                                    <div style="font-size:22px;line-height:1.2;color:#111827;font-weight:700;">
                                        {{ count($cambios) > 1 ? 'REVISIÓN DE PRECIOS' : 'CAMBIO DE PRECIO' }}
                                    </div>
                                    <div style="font-size:14px;line-height:1.4;color:#111827;font-weight:700;">{{ $motivo }}</div>
                                    <div style="font-size:12px;line-height:1.4;color:#4b5563;margin-top:8px;">Emitido: {{ $fechaEnvio }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 18px 0 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e7eb;">
                            <thead>
                                <tr>
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Tipo</th>
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Número</th>
                                    <th align="right" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Precio anterior</th>
                                    <th align="right" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Precio nuevo</th>
                                    <th align="right" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cambios as $index => $c)
                                @php $rowBg = $index % 2 === 0 ? '#f8f8f8' : '#ffffff'; @endphp
                                <tr>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $c->tipo === 'piso' ? 'Piso' : 'Trastero' }}</td>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $c->numero ?? $c->referencia_id }}</td>
                                    <td bgcolor="{{ $rowBg }}" align="right" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ number_format($c->precio_anterior, 2, ',', '.') }} EUR</td>
                                    <td bgcolor="{{ $rowBg }}" align="right" style="padding:9px 8px;font-size:12px;color:#111827;font-weight:700;border-bottom:1px solid #e5e7eb;">{{ number_format($c->precio_nuevo, 2, ',', '.') }} EUR</td>
                                    <td bgcolor="{{ $rowBg }}" align="right" style="padding:9px 8px;font-size:12px;color:{{ $c->precio_nuevo >= $c->precio_anterior ? '#15803d' : '#b91c1c' }};font-weight:700;border-bottom:1px solid #e5e7eb;">
                                        {{ $c->porcentaje !== null ? ($c->porcentaje >= 0 ? '+' : '') . number_format($c->porcentaje, 2, ',', '.') . '%' : '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                @if(count($cambios) > 1)
                <tr>
                    <td style="padding:14px 18px 22px 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <tr>
                                <td align="right">
                                    <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;min-width:280px;">
                                        <tr>
                                            <td style="padding:8px 12px;font-size:12px;color:#4b5563;border:1px solid #e5e7eb;">Unidades actualizadas</td>
                                            <td style="padding:8px 12px;font-size:13px;color:#111827;font-weight:700;border:1px solid #e5e7eb;text-align:right;">{{ count($cambios) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:10px 12px;font-size:12px;color:#111827;font-weight:700;background:#fcc105;border:1px solid #d1a901;">TOTAL MENSUAL ANTES / DESPUÉS</td>
                                            <td style="padding:10px 12px;font-size:13px;color:#111827;font-weight:700;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ number_format($totalAnterior, 2, ',', '.') }} → {{ number_format($totalNuevo, 2, ',', '.') }} EUR</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @else
                <tr><td style="padding:14px 18px 22px 18px;"></td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
