<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Gasto</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f8f8; color: #222; margin:0; padding:0;">
@php
    $logoPath = public_path('logo.jpg');
    $logoExists = file_exists($logoPath);
    $TIPOS = ['agua' => 'Agua', 'luz' => 'Luz', 'comunidad' => 'Comunidad', 'mantenimiento' => 'Mantenimiento', 'otro' => 'Otro'];
    $tipoLabel = $TIPOS[$gasto['tipo']] ?? $gasto['tipo'];
@endphp
<div style="max-width:800px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px #0001;">
    <div style="background:#fcc105;padding:18px 24px 10px 24px;display:flex;align-items:center;">
        @if($logoExists)
            <img src="{{ $logoPath }}" style="height:48px;margin-right:24px;" alt="Logo" />
        @endif
        <div style="flex:1;">
            <h2 style="margin:0;color:#111827;font-size:2rem;letter-spacing:1px;">RECIBO DE GASTO</h2>
            <div style="color:#4b5563;font-size:1.1rem;">{{ $tipoLabel }} — {{ $gasto['descripcion'] }}</div>
        </div>
        <div style="text-align:right;min-width:180px;">
            @if($esDetalle)
                <div style="font-size:1rem;color:#222;font-weight:bold;">Pago Nº {{ $detalle['id'] ?? '-' }}</div>
                <div style="font-size:.95rem;color:#444;">Fecha: {{ \Carbon\Carbon::parse($detalle['fecha_pago'])->format('d/m/Y') }}</div>
            @else
                <div style="font-size:1rem;color:#222;font-weight:bold;">Gasto Nº {{ $gasto['id'] }}</div>
                <div style="font-size:.95rem;color:#444;">Emisión: {{ \Carbon\Carbon::parse($gasto['fecha_emision'])->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
    <div style="padding:28px 32px 18px 32px;">
        <div style="display:flex;gap:32px;align-items:flex-start;margin-bottom:24px;">
            <div style="flex:1;">
                <div style="font-size:1rem;color:#888;font-weight:bold;margin-bottom:4px;">Detalle del gasto</div>
                <div style="font-size:1.05rem;color:#222;font-weight:bold;">{{ $gasto['descripcion'] }}</div>
                <div style="font-size:.95rem;color:#444;">Tipo: {{ $tipoLabel }}</div>
                @if(!empty($gasto['referencia_tipo']) && $gasto['referencia_tipo'] !== 'general')
                <div style="font-size:.95rem;color:#444;">Referencia: {{ ucfirst($gasto['referencia_tipo']) }} #{{ $gasto['referencia_id'] }}</div>
                @endif
            </div>
            <div style="min-width:180px;text-align:right;">
                <div style="font-size:1rem;color:#888;font-weight:bold;margin-bottom:4px;">Importe</div>
                @if($esDetalle)
                    <div style="font-size:1.6rem;color:#111827;font-weight:bold;">{{ number_format($detalle['importe'], 2, ',', '.') }} €</div>
                @else
                    <div style="font-size:1.6rem;color:#111827;font-weight:bold;">{{ number_format($gasto['importe_total'], 2, ',', '.') }} €</div>
                @endif
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <thead>
                <tr style="background:#f3f4f6;">
                    <th style="padding:10px 12px;text-align:left;font-size:.95rem;color:#374151;border:1px solid #e5e7eb;">Concepto</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.95rem;color:#374151;border:1px solid #e5e7eb;">Fecha</th>
                    <th style="padding:10px 12px;text-align:right;font-size:.95rem;color:#374151;border:1px solid #e5e7eb;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @if($esDetalle)
                <tr>
                    <td style="padding:10px 12px;font-size:1rem;color:#222;border:1px solid #e5e7eb;">{{ $gasto['descripcion'] }}</td>
                    <td style="padding:10px 12px;font-size:1rem;color:#222;border:1px solid #e5e7eb;">{{ \Carbon\Carbon::parse($detalle['fecha_pago'])->format('d/m/Y') }}</td>
                    <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ number_format($detalle['importe'], 2, ',', '.') }} €</td>
                </tr>
                @if(!empty($detalle['notas']))
                <tr>
                    <td colspan="3" style="padding:8px 12px;font-size:.9rem;color:#555;border:1px solid #e5e7eb;background:#fafafa;">Notas: {{ $detalle['notas'] }}</td>
                </tr>
                @endif
                @else
                <tr>
                    <td style="padding:10px 12px;font-size:1rem;color:#222;border:1px solid #e5e7eb;">{{ $gasto['descripcion'] }}</td>
                    <td style="padding:10px 12px;font-size:1rem;color:#222;border:1px solid #e5e7eb;">Emisión: {{ \Carbon\Carbon::parse($gasto['fecha_emision'])->format('d/m/Y') }}</td>
                    <td style="padding:10px 12px;font-size:1.1rem;color:#111827;font-weight:bold;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ number_format($gasto['importe_total'], 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:8px 12px;font-size:.95rem;color:#374151;border:1px solid #e5e7eb;">Total pagado</td>
                    <td style="padding:8px 12px;font-size:.95rem;color:#16a34a;font-weight:bold;border:1px solid #e5e7eb;text-align:right;">{{ number_format($gasto['pagado'], 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:8px 12px;font-size:.95rem;color:#374151;border:1px solid #e5e7eb;">Pendiente</td>
                    <td style="padding:8px 12px;font-size:.95rem;color:#dc2626;font-weight:bold;border:1px solid #e5e7eb;text-align:right;">{{ number_format(max(0, $gasto['importe_total'] - $gasto['pagado']), 2, ',', '.') }} €</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div style="margin-top:36px;font-size:1.05rem;color:#222;">Gracias por confiar en <strong>Barna Trasteros</strong>.<br>Un cordial saludo,<br><strong>Miguel Quesada Cantos</strong><br><span style="color:#888">Barna Trasteros</span></div>
    </div>
</div>
</body>
</html>
