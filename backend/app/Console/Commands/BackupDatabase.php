<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Alias histórico: equivale a `backup:crear bd`. */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Genera un backup solo de la base de datos (alias de backup:crear bd)';

    public function handle(): int
    {
        return $this->call('backup:crear', ['tipo' => 'bd']);
    }
}
