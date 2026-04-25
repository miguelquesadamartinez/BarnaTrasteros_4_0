<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura del mes</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f8f8; color: #222; margin:0; padding:0;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px #0001;">
        <div style="background:#fcc105;padding:18px 24px 10px 24px;">
            <h2 style="margin:0;color:#111827;font-size:1.5rem;">Factura del mes</h2>
            <div style="color:#4b5563;font-size:1rem;">{{ $mesNombre }} {{ $anyo }}</div>
        </div>
        <div style="padding:24px;">
            <p>Estimado/a <strong>{{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</strong>,</p>
            <p>Le enviamos adjunta la factura correspondiente al mes de <strong>{{ $mesNombre }} {{ $anyo }}</strong>.
            <br>Por favor, revise los detalles y no dude en contactarnos si necesita cualquier aclaración.</p>
            <h3 style="margin-top:2rem;margin-bottom:.5rem;">Resumen de la factura</h3>
            <ul style="padding-left:1.2em;">
                <li><strong>Cliente:</strong> {{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</li>
                <li><strong>DNI:</strong> {{ $cliente['dni'] }}</li>
                @if(!empty($cliente['direccion']))
                <li><strong>Dirección:</strong> {{ $cliente['direccion'] }}</li>
                @endif
                @if(!empty($cliente['ciudad']))
                <li><strong>Ciudad:</strong> {{ $cliente['codigo_postal'] }} {{ $cliente['ciudad'] }}</li>
                @endif
                <li><strong>Importe total:</strong> {{ number_format($importe_total, 2, ',', '.') }} €</li>
            </ul>
            <h4 style="margin-top:1.5rem;">Conceptos facturados:</h4>
            <ul>
                @foreach($pagos as $p)
                    <li>{{ $p['tipo'] === 'piso' ? 'Piso' : 'Trastero' }} #{{ $p['referencia_id'] ?? $p['numero'] }} — {{ number_format($p['importe_total'], 2, ',', '.') }} €</li>
                @endforeach
            </ul>
            <p style="margin-top:2rem;">Gracias por confiar en <strong>Barna Trasteros</strong>.<br>Un cordial saludo,<br><strong>Miguel Quesada Cantos</strong><br><span style="color:#888">Barna Trasteros</span></p>
        </div>
    </div>
</body>
</html>
