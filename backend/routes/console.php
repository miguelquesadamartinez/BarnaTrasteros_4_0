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

// Generar los pagos del mes en curso. Se lanza cada hora (y no solo el día 1)
// para que, si el equipo estaba apagado el día 1, se generen en cuanto vuelva
// a encenderse. Es idempotente: no duplica recibos existentes y solo envía el
// email de reporte si ha creado alguno.
Schedule::job(new GenerarPagosMensuales)->hourlyAt(5)->name('pagos:generar-mes-en-curso')->withoutOverlapping();
//Schedule::job(new GenerarPagosMensuales)->everyThirtyMinutes();

// Backup automático diario a las 23:00
// Copia completa automática (base de datos + archivos) cada noche. Se conservan
// las últimas BACKUP_RETENCION (30 por defecto); las más antiguas se borran solas.
Schedule::command('backup:crear completo --auto')->dailyAt('23:00')->name('backup:nocturno')->withoutOverlapping();

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

// (El antiguo backup horario solo en producción se ha sustituido por la copia completa nocturna.)


// Comando artisan manual para generar pagos de un mes/año específico
Artisan::command('pagos:generar {mes?} {anyo?}', function ($mes = null, $anyo = null) {
    $mes  = $mes  ?? now()->month;
    $anyo = $anyo ?? now()->year;
    GenerarPagosMensuales::dispatchSync((int)$mes, (int)$anyo);
    $this->info("Pagos generados para {$mes}/{$anyo}");
})->purpose('Generar pagos mensuales de alquiler para el mes/año indicado');
