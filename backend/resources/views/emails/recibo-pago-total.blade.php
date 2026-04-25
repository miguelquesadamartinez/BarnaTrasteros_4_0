<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de pago mensual</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f8f8; color: #222; margin:0; padding:0;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px #0001;">
        <div style="background:#fcc105;padding:18px 24px 10px 24px;">
            <h2 style="margin:0;color:#111827;font-size:1.5rem;">Recibo mensual</h2>
            <div style="color:#4b5563;font-size:1rem;">{{ $pago['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $pago['numero'] ?? $pago['referencia_id'] }} — {{ $pago['mes'] }}/{{ $pago['anyo'] }}</div>
        </div>
        <div style="padding:24px;">
            <p>Estimado/a <strong>{{ $pago['cliente']['nombre'] ?? '' }} {{ $pago['cliente']['apellido'] ?? '' }}</strong>,</p>
            <p>Le enviamos adjunto el recibo correspondiente al pago mensual de <strong>{{ $pago['mes'] }}/{{ $pago['anyo'] }}</strong> por importe de <strong>{{ number_format($pago['importe_total'], 2, ',', '.') }} €</strong>.
            <br>Por favor, revise los detalles y no dude en contactarnos si necesita cualquier aclaración.</p>
            <ul style="padding-left:1.2em;">
                <li><strong>Cliente:</strong> {{ $pago['cliente']['nombre'] ?? '' }} {{ $pago['cliente']['apellido'] ?? '' }}</li>
                <li><strong>DNI:</strong> {{ $pago['cliente']['dni'] ?? '' }}</li>
                <li><strong>Referencia:</strong> {{ $pago['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} {{ $pago['numero'] ?? $pago['referencia_id'] }}</li>
                <li><strong>Mes/Año:</strong> {{ $pago['mes'] }}/{{ $pago['anyo'] }}</li>
                <li><strong>Importe total:</strong> {{ number_format($pago['importe_total'], 2, ',', '.') }} €</li>
            </ul>
            <p style="margin-top:2rem;">Gracias por confiar en <strong>Barna Trasteros</strong>.<br>Un cordial saludo,<br><strong>Miguel Quesada Cantos</strong><br><span style="color:#888">Barna Trasteros</span></p>
        </div>
    </div>
</body>
</html>
