<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Backup de la base de datos</title>
</head>
<body style="font-family: Arial, sans-serif; background:#ffffff; color:#222; margin:0; padding:0;">
<div style="max-width:600px;margin:0 auto;padding:24px;">
    <h2 style="margin:0 0 12px 0;color:#111827;">🗄️ Backup de la base de datos</h2>
    <p style="font-size:1.02rem;">Se adjunta el volcado de la base de datos generado el <strong>{{ $fecha }}</strong>.</p>
    <table style="border-collapse:collapse;margin-top:12px;">
        <tr>
            <td style="padding:6px 10px;border:1px solid #e5e7eb;font-weight:bold;">Archivo</td>
            <td style="padding:6px 10px;border:1px solid #e5e7eb;font-family:monospace;">{{ $filename }}</td>
        </tr>
        <tr>
            <td style="padding:6px 10px;border:1px solid #e5e7eb;font-weight:bold;">Tamaño</td>
            <td style="padding:6px 10px;border:1px solid #e5e7eb;">{{ $sizeKb }} KB</td>
        </tr>
    </table>
</div>
</body>
</html>
