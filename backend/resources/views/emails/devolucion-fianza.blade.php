<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de devolución de fianza</title>
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
            <h2 style="margin:0;color:#111827;font-size:1.8rem;letter-spacing:1px;">DEVOLUCIÓN DE FIANZA</h2>
            <div style="color:#4b5563;font-size:1.1rem;">
                @if($fianza['numero'])
                    {{ $fianza['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $fianza['numero'] }}
                @endif
            </div>
        </div>
        <div style="text-align:right;min-width:180px;">
            <div style="font-size:1rem;color:#222;font-weight:bold;">Referencia: {{ $fianza['id'] ?? '-' }}</div>
            <div style="font-size:.95rem;color:#444;">Fecha: {{ \Carbon\Carbon::parse($fianza['fecha_devolucion'])->format('d/m/Y') }}</div>
        </div>
    </div>
    <div style="padding:28px 32px 18px 32px;">
        <div style="display:flex;gap:32px;align-items:flex-start;">
            <div style="flex:1;min-width:220px;">
                <div style="font-size:1.1rem;color:#111827;font-weight:bold;margin-bottom:6px;">Cliente:</div>
                <div style="font-size:1.05rem;color:#222;font-weight:bold;">{{ $cliente['nombre'] ?? '' }} {{ $cliente['apellido'] ?? '' }}</div>
                <div style="font-size:.98rem;color:#444;">DNI: {{ $cliente['dni'] ?? '' }}</div>
            </div>
            <div style="flex:1;min-width:180px;">
                <div style="font-size:1.05rem;color:#222;font-weight:bold;margin-bottom:6px;">Importe devuelto:</div>
                <div style="font-size:1.2rem;color:#111827;font-weight:bold;">{{ number_format($fianza['importe'], 2, ',', '.') }} €</div>
            </div>
        </div>

        <div style="margin-top:32px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Concepto</th>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Fecha entrega</th>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Fecha devolución</th>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:right;border:1px solid #e5e7eb;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background:#f8f8f8;">
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            Fianza {{ $fianza['tipo'] === 'piso' ? 'Piso' : ($fianza['tipo'] === 'trastero' ? 'Trastero' : '') }} {{ $fianza['numero'] ?? '' }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ \Carbon\Carbon::parse($fianza['fecha_entrega'])->format('d/m/Y') }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ \Carbon\Carbon::parse($fianza['fecha_devolucion'])->format('d/m/Y') }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#111827;font-weight:bold;text-align:right;border:1px solid #e5e7eb;">
                            {{ number_format($fianza['importe'], 2, ',', '.') }} €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(!empty($fianza['notas']))
        <div style="margin-top:20px;font-size:.95rem;color:#444;">
            <strong>Notas:</strong> {{ $fianza['notas'] }}
        </div>
        @endif

        <div style="margin-top:32px;font-size:1.05rem;color:#222;">
            Este documento certifica que la fianza correspondiente ha sido devuelta íntegramente al cliente en la fecha indicada.
        </div>

        <div style="margin-top:32px;font-size:1.05rem;color:#222;">Gracias por confiar en <strong>Barna Trasteros</strong>.<br>Un cordial saludo,<br><strong>Miguel Quesada Cantos</strong><br><span style="color:#888">Barna Trasteros</span></div>
    </div>
</div>
</body>
</html>
