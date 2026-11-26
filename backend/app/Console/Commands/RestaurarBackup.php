<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RestaurarBackup extends Command
{
    protected $signature = 'backup:restaurar
        {archivo : Nombre de la copia en mysql_bk}
        {--solo-bd : Restaurar solo la base de datos}
        {--solo-archivos : Restaurar solo los archivos}
        {--sin-copia-seguridad : No crear antes una copia del estado actual}
        {--force : No pedir confirmación}';

    protected $description = 'Restaura una copia de seguridad (todo, solo la base de datos o solo los archivos)';

    public function handle(BackupService $backups): int
    {
        try {
            $info = $backups->info($this->argument('archivo'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bd = $info['contiene']['bd'] && !$this->option('solo-archivos');
        $archivos = $info['contiene']['archivos'] && !$this->option('solo-bd');

        $partes = implode(' y ', array_filter([$bd ? 'la base de datos' : null, $archivos ? 'los archivos' : null]));
        if (!$this->option('force') && !$this->confirm("Se sustituirán {$partes} por los de {$info['filename']}. ¿Continuar?")) {
            return self::FAILURE;
        }

        try {
            $res = $backups->restaurar($info['filename'], $bd, $archivos, !$this->option('sin-copia-seguridad'));
        } catch (\Throwable $e) {
            $this->error('Error al restaurar: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Restaurado: {$partes}.");
        if ($res['copia_seguridad']) {
            $this->info("Copia del estado anterior: {$res['copia_seguridad']}");
        }

        return self::SUCCESS;
    }
}
