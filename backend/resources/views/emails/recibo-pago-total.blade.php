<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo mensual</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f8f8; color: #222; margin:0; padding:0;">
@php
    $logoPath = public_path('logo.jpg');
    $logoExists = file_exists($logoPath);
@endphp
<div style="max-width:800px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px #0001;">
    <div style="background:#fcc105;padding:18px 24px 10px 24px;display:flex;align-items:center;">
        @if($logoExists)
            <img src="{{ $logoPath }}" style="height:48px;margin-right:24px;" alt="Logo" />
        @endif
        <div style="flex:1;">
            <h2 style="margin:0;color:#111827;font-size:2rem;letter-spacing:1px;">RECIBO MENSUAL</h2>
            <div style="color:#4b5563;font-size:1.1rem;">{{ $pago['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $pago['numero'] ?? $pago['referencia_id'] }} — {{ $pago['mes'] }}/{{ $pago['anyo'] }}</div>
        </div>
        <div style="text-align:right;min-width:180px;">
            <div style="font-size:1rem;color:#222;font-weight:bold;">Referencia: {{ $pago['id'] ?? '-' }}</div>
            <div style="font-size:.95rem;color:#444;">Fecha: {{ date('d/m/Y') }}</div>
        </div>
    </div>
    <div style="padding:28px 32px 18px 32px;">
        <div style="display:flex;gap:32px;align-items:flex-start;">
            <div style="flex:1;min-width:220px;">
                <div style="font-size:1.1rem;color:#111827;font-weight:bold;margin-bottom:6px;">Cliente:</div>
                <div style="font-size:1.05rem;color:#222;font-weight:bold;">{{ $pago['cliente']['nombre'] ?? '' }} {{ $pago['cliente']['apellido'] ?? '' }}</div>
                <div style="font-size:.98rem;color:#444;">DNI: {{ $pago['cliente']['dni'] ?? '' }}</div>
                <div style="font-size:.98rem;color:#444;">{{ $pago['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $pago['numero'] ?? $pago['referencia_id'] }}</div>
            </div>
            <div style="flex:1;min-width:180px;">
                <div style="font-size:1.05rem;color:#222;font-weight:bold;margin-bottom:6px;">Importe total:</div>
                <div style="font-size:1.2rem;color:#111827;font-weight:bold;">{{ number_format($pago['importe_total'], 2, ',', '.') }} €</div>
            </div>
        </div>

        <div style="margin-top:32px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Concepto</th>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Detalle</th>
                        <th style="background:#fcc105;color:#111827;font-size:1rem;padding:10px 8px;text-align:right;border:1px solid #e5e7eb;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background:#f8f8f8;">
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            Arrendamiento {{ $pago['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $pago['numero'] ?? $pago['referencia_id'] }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#222;border:1px solid #e5e7eb;">
                            {{ $pago['mes'] }}/{{ $pago['anyo'] }}
                        </td>
                        <td style="padding:9px 8px;font-size:.98rem;color:#111827;font-weight:bold;text-align:right;border:1px solid #e5e7eb;">
                            {{ number_format($pago['importe_total'], 2, ',', '.') }} €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top:32px;display:flex;gap:32px;align-items:flex-end;">
            <div style="flex:1;"></div>
            <div style="min-width:220px;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;">TOTAL RECIBO</td>
                        <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ number_format($pago['importe_total'], 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="margin-top:36px;font-size:1.05rem;color:#222;">Gracias por confiar en <strong>Barna Trasteros</strong>.<br>Un cordial saludo,<br><strong>Miguel Quesada Cantos</strong><br><span style="color:#888">Barna Trasteros</span></div>
    </div>
</div>
</body>
</html>
