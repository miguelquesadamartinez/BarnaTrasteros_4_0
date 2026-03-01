<?php

use App\Jobs\GenerarPagosMensuales;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generar pagos mensuales el primer día de cada mes a las 00:05
Schedule::job(new GenerarPagosMensuales)->monthlyOn(1, '00:05');

// Comando artisan manual para generar pagos de un mes/año específico
Artisan::command('pagos:generar {mes?} {anyo?}', function ($mes = null, $anyo = null) {
    $mes  = $mes  ?? now()->month;
    $anyo = $anyo ?? now()->year;
    GenerarPagosMensuales::dispatchSync((int)$mes, (int)$anyo);
    $this->info("Pagos generados para {$mes}/{$anyo}");
})->purpose('Generar pagos mensuales de alquiler para el mes/año indicado');
