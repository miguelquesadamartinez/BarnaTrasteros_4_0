<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

/** Alias histórico: restaura solo la base de datos de una copia (equivale a `backup:restaurar --solo-bd`). */
class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {filename? : Nombre del archivo de backup}';

    protected $description = 'Restaura la base de datos desde un backup de la carpeta mysql_bk';

    public function handle(BackupService $backups): int
    {
        $filename = $this->argument('filename');
        if (!$filename) {
            $opciones = array_column(array_filter($backups->listar(), fn ($b) => $b['contiene']['bd']), 'filename');
            if (!$opciones) {
                $this->error('No hay copias con base de datos en mysql_bk.');

                return self::FAILURE;
            }
            $filename = $this->choice('Selecciona la copia a restaurar:', $opciones, 0);
        }

        return $this->call('backup:restaurar', ['archivo' => $filename, '--solo-bd' => true, '--force' => true]);
    }
}
