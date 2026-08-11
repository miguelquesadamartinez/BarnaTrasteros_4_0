<?php

namespace App\Console\Commands;

use App\Models\ListaEspera;
use Illuminate\Console\Command;

class LimpiarListaEspera extends Command
{
    protected $signature   = 'lista-espera:limpiar';
    protected $description = 'Elimina de la lista de espera los registros con más de 2 meses de antigüedad';

    public function handle(): int
    {
        $borrados = ListaEspera::where('created_at', '<', now()->subMonths(2))->delete();

        $this->info("Eliminados {$borrados} registros de la lista de espera con más de 2 meses.");

        return self::SUCCESS;
    }
}
