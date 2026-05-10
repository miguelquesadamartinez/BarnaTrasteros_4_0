<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Excepción en BarnaTrasteros</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        h2 { color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 6px 10px; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; width: 160px; }
        pre { background: #f9f9f9; padding: 10px; font-size: 12px; overflow: auto; border: 1px solid #ddd; max-height: 400px; }
    </style>
</head>
<body>
    <h2>⚠️ Excepción en BarnaTrasteros Backend</h2>

    <table>
        <tr><th>Mensaje</th><td>{{ $content['message'] ?? '-' }}</td></tr>
        <tr><th>Archivo</th><td>{{ $content['file'] ?? '-' }}</td></tr>
        <tr><th>Línea</th><td>{{ $content['line'] ?? '-' }}</td></tr>
        <tr><th>URL</th><td>{{ $content['fullUrl'] ?? $content['url'] ?? '-' }}</td></tr>
        <tr><th>IP</th><td>{{ $content['ip'] ?? '-' }}</td></tr>
        @if(!empty($content['body']))
        <tr>
            <th>Body</th>
            <td><pre>{{ json_encode($content['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td>
        </tr>
        @endif
    </table>

    @if(!empty($content['trace']))
    <h3>Stack Trace</h3>
    <pre>{{ collect($content['trace'])->take(20)->map(fn($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' — ' . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? ''))->implode("\n") }}</pre>
    @endif
</body>
</html>
