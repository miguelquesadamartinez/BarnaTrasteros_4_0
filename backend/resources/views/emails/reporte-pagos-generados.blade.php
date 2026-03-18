<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de pagos generados</title>
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
                                        <img src="{{ $logoCid }}" alt="Barna Trasteros" style="display:block;width:140px;max-width:100%;height:auto;margin-bottom:8px;">
                                    @endif
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">C/ Velia, 81 - 08016 - Barcelona</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">Miguel Quesada Cantos</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">DNI 36945618M</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">Telf: 696 412 959 - 93 352 2003</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">www.barnatrasteros.com</div>
                                    <div style="font-size:12px;line-height:1.45;color:#111827;font-weight:700;">info@barnatrasteros.com</div>
                                </td>
                                <td valign="top" align="right" style="width:40%;">
                                    <div style="font-size:22px;line-height:1.2;color:#111827;font-weight:700;">REPORTE</div>
                                    <div style="font-size:14px;line-height:1.4;color:#111827;font-weight:700;">Pagos generados</div>
                                    <div style="font-size:12px;line-height:1.4;color:#4b5563;margin-top:8px;">Periodo: {{ sprintf('%02d', $mes) }}/{{ $anyo }} ({{ $mesNombre }})</div>
                                    <div style="font-size:12px;line-height:1.4;color:#4b5563;">Emitido: {{ $fechaEnvio }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 18px 0 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="width:33.33%;padding-right:8px;">
                                    <div style="background:#f8fafc;border:1px solid #e5e7eb;padding:12px 10px;">
                                        <div style="font-size:11px;color:#6b7280;">Registros generados</div>
                                        <div style="font-size:22px;color:#111827;font-weight:700;line-height:1.2;">{{ $totalRegistros }}</div>
                                    </div>
                                </td>
                                <td style="width:33.33%;padding:0 4px;">
                                    <div style="background:#f8fafc;border:1px solid #e5e7eb;padding:12px 10px;">
                                        <div style="font-size:11px;color:#6b7280;">Trasteros</div>
                                        <div style="font-size:22px;color:#111827;font-weight:700;line-height:1.2;">{{ $totalTrasteros }}</div>
                                    </div>
                                </td>
                                <td style="width:33.33%;padding-left:8px;">
                                    <div style="background:#f8fafc;border:1px solid #e5e7eb;padding:12px 10px;">
                                        <div style="font-size:11px;color:#6b7280;">Pisos</div>
                                        <div style="font-size:22px;color:#111827;font-weight:700;line-height:1.2;">{{ $totalPisos }}</div>
                                    </div>
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
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Concepto</th>
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Detalle</th>
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Cliente</th>
                                    <th align="left" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">NIF</th>
                                    <th align="right" style="background:#fcc105;padding:9px 8px;font-size:12px;color:#111827;">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pagos as $index => $pago)
                                @php
                                    $tipoLabel = $pago->tipo === 'piso' ? 'Arrendamiento Piso' : 'Arrendamiento Trastero';
                                    $detalle = ($pago->tipo === 'piso' ? 'Piso ' : 'Trastero ') . ($pago->numero ?: $pago->referencia_id) . ' - ' . sprintf('%02d', $pago->mes) . '/' . $pago->anyo;
                                    $clienteNombre = $pago->cliente ? trim(($pago->cliente->nombre ?? '') . ' ' . ($pago->cliente->apellido ?? '')) : 'Sin cliente';
                                    $dni = $pago->cliente->dni ?? '-';
                                    $rowBg = $index % 2 === 0 ? '#f8f8f8' : '#ffffff';
                                @endphp
                                <tr>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $tipoLabel }}</td>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $detalle }}</td>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $clienteNombre }}</td>
                                    <td bgcolor="{{ $rowBg }}" style="padding:9px 8px;font-size:12px;color:#1f2937;border-bottom:1px solid #e5e7eb;">{{ $dni }}</td>
                                    <td bgcolor="{{ $rowBg }}" align="right" style="padding:9px 8px;font-size:12px;color:#111827;font-weight:700;border-bottom:1px solid #e5e7eb;">{{ number_format((float) $pago->importe_total, 2, ',', '.') }} EUR</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" style="padding:16px 10px;font-size:13px;color:#4b5563;text-align:center;">No se han generado nuevos pagos para este periodo.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:14px 18px 22px 18px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <tr>
                                <td align="right">
                                    <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;min-width:280px;">
                                        <tr>
                                            <td style="padding:8px 12px;font-size:12px;color:#4b5563;border:1px solid #e5e7eb;">TOTAL REPORTE</td>
                                            <td style="padding:8px 12px;font-size:13px;color:#111827;font-weight:700;border:1px solid #e5e7eb;text-align:right;">{{ number_format((float) $totalImporte, 2, ',', '.') }} EUR</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:10px 12px;font-size:12px;color:#111827;font-weight:700;background:#fcc105;border:1px solid #d1a901;">Periodo</td>
                                            <td style="padding:10px 12px;font-size:12px;color:#111827;font-weight:700;background:#fcc105;border:1px solid #d1a901;text-align:right;">{{ $mesNombre }} {{ $anyo }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
