<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de arrendamiento</title>
</head>
<body style="font-family: Arial, sans-serif; background: #ffffff; color: #222; margin:0; padding:0; font-size: .92rem; line-height:1.5;">
@php
    $logoSrc = file_exists(public_path('logo.jpg')) ? public_path('logo.jpg') : null;
    $unidades = array_merge(
        array_map(fn ($t) => ['tipo' => 'Trastero', 'numero' => $t['numero'], 'detalle' => trim(($t['piso'] ?? '') . ' · ' . ($t['tamanyo'] ?? '')), 'precio' => $t['precio_mensual']], $trasteros),
        array_map(fn ($p) => ['tipo' => 'Piso', 'numero' => $p['numero'], 'detalle' => $p['piso'] ?? '', 'precio' => $p['precio_mensual']], $pisos)
    );
    $totalRenta = array_sum(array_column($unidades, 'precio'));
    $totalFianza = collect($fianzas)->sum('importe');
@endphp
<div style="max-width:800px;margin:0 auto;background:#fff;">
    <div style="background:#fcc105;padding:18px 24px;display:flex;align-items:center;">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" style="height:48px;margin-right:24px;" alt="Logo" />
        @endif
        <div style="flex:1;">
            <h2 style="margin:0;color:#111827;font-size:1.6rem;letter-spacing:1px;">CONTRATO DE ARRENDAMIENTO</h2>
            <div style="color:#4b5563;font-size:1rem;">{{ count($unidades) > 1 ? 'Trasteros / Pisos' : ($trasteros ? 'Trastero' : 'Piso') }}</div>
        </div>
        <div style="text-align:right;min-width:160px;">
            <div style="font-size:.95rem;color:#444;">Barcelona, {{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</div>
        </div>
    </div>

    <div style="padding:28px 32px;">
        <p>
            <strong>REUNIDOS</strong><br><br>
            <strong>DE UNA PARTE</strong>, como ARRENDADOR, {{ $empresa['nombre'] ?: '_______________________' }},
            @if($empresa['dni_nif'])con DNI/NIF {{ $empresa['dni_nif'] }}, @endif
            @if($empresa['responsable'] && $empresa['responsable'] !== $empresa['nombre'])representado por {{ $empresa['responsable'] }}, @endif
            con domicilio en {{ $empresa['direccion'] ?: '_______________________' }}@if($empresa['telefono']), teléfono de contacto {{ $empresa['telefono'] }}@endif.
        </p>
        <p>
            <strong>Y DE OTRA PARTE</strong>, como ARRENDATARIO, <strong>{{ $cliente['nombre'] }} {{ $cliente['apellido'] }}</strong>,
            con DNI {{ $cliente['dni'] }},
            @if(!empty($cliente['direccion']))con domicilio en {{ $cliente['direccion'] }}{{ !empty($cliente['ciudad']) ? ', ' . $cliente['codigo_postal'] . ' ' . $cliente['ciudad'] : '' }}, @endif
            @if(!empty($cliente['telefono']))teléfono {{ $cliente['telefono'] }}, @endif
            @if(!empty($cliente['email']))email {{ $cliente['email'] }}, @endif
            en adelante EL ARRENDATARIO.
        </p>
        <p>Ambas partes se reconocen mutuamente capacidad legal suficiente para suscribir el presente contrato de arrendamiento, y a tal efecto</p>
        <p><strong>EXPONEN Y ACUERDAN</strong> las siguientes</p>
        <p style="text-align:center;"><strong>CLÁUSULAS</strong></p>

        <p><strong>PRIMERA. — Objeto.</strong> EL ARRENDADOR cede en arrendamiento a EL ARRENDATARIO, quien lo acepta, el/los siguiente(s) espacio(s):</p>
        <table style="width:100%;border-collapse:collapse;margin:.5rem 0 1rem;">
            <thead>
                <tr>
                    <th style="background:#fcc105;color:#111827;font-size:.9rem;padding:8px;text-align:left;border:1px solid #e5e7eb;">Tipo</th>
                    <th style="background:#fcc105;color:#111827;font-size:.9rem;padding:8px;text-align:left;border:1px solid #e5e7eb;">Número</th>
                    <th style="background:#fcc105;color:#111827;font-size:.9rem;padding:8px;text-align:left;border:1px solid #e5e7eb;">Detalle</th>
                    <th style="background:#fcc105;color:#111827;font-size:.9rem;padding:8px;text-align:right;border:1px solid #e5e7eb;">Renta mensual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unidades as $i => $u)
                    <tr @if($i % 2 === 0) style="background:#f8f8f8;" @endif>
                        <td style="padding:7px 8px;border:1px solid #e5e7eb;">{{ $u['tipo'] }}</td>
                        <td style="padding:7px 8px;border:1px solid #e5e7eb;">{{ $u['numero'] }}</td>
                        <td style="padding:7px 8px;border:1px solid #e5e7eb;">{{ $u['detalle'] ?: '—' }}</td>
                        <td style="padding:7px 8px;border:1px solid #e5e7eb;text-align:right;">{{ number_format($u['precio'], 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p><strong>SEGUNDA. — Duración.</strong> El presente contrato entra en vigor en la fecha de su firma y tendrá duración indefinida, renovándose tácitamente por periodos mensuales, salvo que cualquiera de las partes comunique a la otra su voluntad de resolverlo con un preaviso mínimo de 15 días naturales.</p>

        <p><strong>TERCERA. — Renta.</strong> La renta mensual total asciende a <strong>{{ number_format($totalRenta, 2, ',', '.') }} €</strong>, que EL ARRENDATARIO abonará por mensualidades anticipadas, mediante transferencia bancaria u otro medio acordado entre las partes, dentro de los primeros 5 días de cada mes.</p>

        <p><strong>CUARTA. — Fianza.</strong>
        @if($totalFianza > 0)
            EL ARRENDATARIO entrega en este acto, en concepto de fianza, la cantidad de <strong>{{ number_format($totalFianza, 2, ',', '.') }} €</strong>, que EL ARRENDADOR devolverá a la finalización del contrato, previa comprobación del buen estado del/de los espacio(s) arrendado(s) y una vez liquidada cualquier cantidad pendiente.
        @else
            No se ha establecido fianza para este contrato.
        @endif
        </p>

        <p><strong>QUINTA. — Uso y conservación.</strong> EL ARRENDATARIO se compromete a destinar el espacio arrendado a un uso lícito y acorde con su naturaleza, a mantenerlo en buen estado de conservación y limpieza, y a no almacenar materiales peligrosos, inflamables, insalubres o ilegales. EL ARRENDATARIO responderá de los daños causados por un uso indebido del espacio.</p>

        <p><strong>SEXTA. — Obligaciones y resolución.</strong> El impago de dos o más mensualidades, así como el incumplimiento de cualquiera de las cláusulas anteriores, facultará a EL ARRENDADOR para resolver el presente contrato y recuperar la posesión del espacio arrendado, sin perjuicio de las cantidades que pudieran adeudarse.</p>

        <p>Y en prueba de conformidad, ambas partes firman el presente contrato por duplicado y a un solo efecto, en el lugar y fecha indicados.</p>

        <div style="margin-top:56px;display:flex;justify-content:space-between;">
            <div style="width:45%;text-align:center;">
                <div style="border-top:1px solid #444;padding-top:6px;">EL ARRENDADOR</div>
            </div>
            <div style="width:45%;text-align:center;">
                <div style="border-top:1px solid #444;padding-top:6px;">EL ARRENDATARIO</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
