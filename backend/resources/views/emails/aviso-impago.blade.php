<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aviso de pago pendiente</title>
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
                {{ count($filas) > 1 ? 'AVISO DE PAGOS PENDIENTES' : 'AVISO DE PAGO PENDIENTE' }}
            </h2>
        </div>
    </div>
    <div style="padding:28px 32px 18px 32px;">
        <p style="font-size:1.05rem;">Estimado/a <strong>{{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</strong>,</p>
        <p style="font-size:1.02rem;">
            Le informamos de que {{ count($filas) > 1 ? 'los siguientes pagos de alquiler continúan pendientes' : 'el siguiente pago de alquiler continúa pendiente' }}:
        </p>

        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <thead>
                <tr>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:left;border:1px solid #e5e7eb;">Unidad</th>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:left;border:1px solid #e5e7eb;">Periodo</th>
                    <th style="background:#fcc105;color:#111827;font-size:1rem;padding:9px 8px;text-align:right;border:1px solid #e5e7eb;">Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $i => $f)
                    <tr @if($i % 2 === 0) style="background:#f8f8f8;" @endif>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ $f['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $f['numero'] }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ $f['mesNombre'] }} {{ $f['anyo'] }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#111827;font-weight:bold;text-align:right;border:1px solid #e5e7eb;">
                            {{ number_format($f['pendiente'], 2, ',', '.') }} €
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;display:flex;justify-content:flex-end;">
            <table style="border-collapse:collapse;min-width:260px;">
                <tr>
                    <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;">TOTAL PENDIENTE</td>
                    <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ number_format($totalPendiente, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>

        <p style="font-size:1.02rem;margin-top:24px;">
            Le rogamos regularice esta cantidad a la mayor brevedad posible. Si ya ha realizado alguno de estos pagos, puede ignorar la parte correspondiente de este aviso.
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
