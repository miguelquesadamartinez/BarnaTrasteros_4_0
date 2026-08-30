<?php

namespace App\Jobs;

use App\Mail\ReportePagosPendientesMail;
use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportarPagosPendientesSemanal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $pagos = PagoAlquiler::with('cliente')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('anyo')
            ->orderBy('mes')
            ->get();

        $pendientes = $pagos->map(function (PagoAlquiler $pago) {
            $mesNombre = ucfirst(Carbon::create()->month($pago->mes)->locale('es')->monthName);
            return [
                'cliente_nombre' => $pago->cliente ? trim("{$pago->cliente->nombre} {$pago->cliente->apellido}") : 'Sin cliente',
                'tipo' => $pago->tipo,
                'numero' => $pago->numero ?? $pago->referencia_id,
                'mesNombre' => $mesNombre,
                'anyo' => $pago->anyo,
                'estado' => $pago->estado,
                'pendiente' => max(0, (float) $pago->importe_total - (float) $pago->pagado),
            ];
        });

        if ($pendientes->isEmpty()) {
            Log::info('ReportarPagosPendientesSemanal: sin pagos pendientes, no se envía email.');
            return;
        }

        $totalPendiente = $pendientes->sum('pendiente');

        $destinatario = (string) config('mail.reportes.pagos_to');
        Mail::to($destinatario)->queue(new ReportePagosPendientesMail($pendientes, $totalPendiente));

        Log::info("ReportarPagosPendientesSemanal: reporte enviado a {$destinatario} con {$pendientes->count()} pagos pendientes (total {$totalPendiente}).");
    }
}
