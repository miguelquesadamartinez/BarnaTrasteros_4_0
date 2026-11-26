<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CrearBackup extends Command
{
    protected $signature = 'backup:crear
        {tipo=completo : completo | bd | archivos}
        {--auto : Marca la copia como automática (se le aplica la retención)}';

    protected $description = 'Crea una copia de seguridad (completa, solo base de datos o solo archivos) en mysql_bk';

    public function handle(BackupService $backups): int
    {
        try {
            $info = $backups->crear($this->argument('tipo'), $this->option('auto') ? 'auto' : 'manual');
        } catch (\Throwable $e) {
            $this->error('Error al crear la copia: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Copia creada: %s (%s KB)', $info['filename'], round($info['bytes'] / 1024, 1)));

        return self::SUCCESS;
    }
}
