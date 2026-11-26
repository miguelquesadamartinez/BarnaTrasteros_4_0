<?php

return [
    // Carpeta donde se guardan las copias (montada fuera del contenedor).
    'directorio' => env('BACKUP_DIR', base_path('mysql_bk')),

    // Archivos subidos por la aplicación: fotos de DNI, contratos e imágenes de gastos.
    'archivos_path' => env('BACKUP_FILES_PATH', storage_path('app/public')),

    // Copias automáticas (nocturnas) que se conservan; las más antiguas se borran.
    'retencion_automaticas' => (int) env('BACKUP_RETENCION', 30),

    // Copias de seguridad previas a una restauración que se conservan.
    'retencion_seguridad' => (int) env('BACKUP_RETENCION_SEGURIDAD', 10),

    // Tamaño máximo para enviar una copia por email (Gmail admite 25 MB).
    'email_max_mb' => (int) env('BACKUP_EMAIL_MAX_MB', 20),

    // Hora del backup automático (solo informativa para la pantalla; el horario real está en routes/console.php).
    'hora_automatica' => '23:00',
];
