<?php

use App\Jobs\AvisarImpagos;
use App\Jobs\GenerarPagosMensuales;
use App\Jobs\ReportarPagosPendientesSemanal;
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

// Limpiar lista de espera (más de 2 meses) todos los días a las 10:00
Schedule::command('lista-espera:limpiar')->dailyAt('10:00');

// Avisar de impagos (pagos vencidos hace 5+ días sin avisar aún) todos los días a las 09:00
Schedule::job(new AvisarImpagos)->dailyAt('09:00');

// Comando manual para lanzar el aviso de impagos fuera de horario
Artisan::command('pagos:avisar-impagos', function () {
    AvisarImpagos::dispatchSync();
    $this->info('Aviso de impagos ejecutado.');
})->purpose('Avisar por email de los pagos pendientes vencidos hace más de 5 días');

// Reporte semanal de pagos pendientes, todos los lunes a las 08:00
Schedule::job(new ReportarPagosPendientesSemanal)->weeklyOn(1, '08:00');

// Comando manual para lanzar el reporte semanal fuera de horario
Artisan::command('pagos:reportar-pendientes', function () {
    ReportarPagosPendientesSemanal::dispatchSync();
    $this->info('Reporte semanal de pagos pendientes enviado.');
})->purpose('Enviar por email el reporte semanal de pagos pendientes a REPORT_PAGOS_EMAIL');

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
