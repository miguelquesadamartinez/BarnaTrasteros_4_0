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
//Schedule::job(new GenerarPagosMensuales)->everyThirtyMinutes();

// Backup automático diario a las 23:00
Schedule::command('db:backup')->dailyAt('23:00');

//Schedule::command('db:backup')->hourly();
//Schedule::command('db:backup')->everyThirtyMinutes();

Schedule::command('db:backup')->hourly()->when(function () {
    // Solo ejecutar el backup automático si estamos en producción
    return app()->environment('production');
})->name('backup:hourly')->withoutOverlapping()->runInBackground();


// Comando artisan manual para generar pagos de un mes/año específico
Artisan::command('pagos:generar {mes?} {anyo?}', function ($mes = null, $anyo = null) {
    $mes  = $mes  ?? now()->month;
    $anyo = $anyo ?? now()->year;
    GenerarPagosMensuales::dispatchSync((int)$mes, (int)$anyo);
    $this->info("Pagos generados para {$mes}/{$anyo}");
})->purpose('Generar pagos mensuales de alquiler para el mes/año indicado');
