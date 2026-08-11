<?php

namespace App\Jobs;

use App\Mail\ReporteImpagosMail;
use App\Models\PagoAlquiler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AvisarImpagos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // El alquiler vence el día 5 de cada mes (según el contrato); se avisa 5 días después.
    private const DIAS_MARGEN = 5;

    public function handle(): void
    {
        $pagos = PagoAlquiler::with('cliente')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereNull('aviso_impago_enviado_at')
            ->get();

        $impagosParaReporte = [];
        $totalPendiente = 0.0;

        foreach ($pagos as $pago) {
            $fechaAviso = Carbon::create($pago->anyo, $pago->mes, 5)->addDays(self::DIAS_MARGEN);
            if (Carbon::now()->lt($fechaAviso)) {
                continue;
            }

            $cliente = $pago->cliente;
            if (!$pago->enviarAvisoImpago()) {
                Log::info("AvisarImpagos: pago {$pago->id} sin cliente/email, se omite el aviso individual.");
                continue;
            }

            $pendiente = max(0, (float) $pago->importe_total - (float) $pago->pagado);
            $mesNombre = ucfirst(Carbon::create()->month($pago->mes)->locale('es')->monthName);

            $impagosParaReporte[] = [
                'cliente_nombre' => trim("{$cliente->nombre} {$cliente->apellido}"),
                'cliente_dni' => $cliente->dni,
                'tipo' => $pago->tipo,
                'numero' => $pago->numero ?? $pago->referencia_id,
                'mesNombre' => $mesNombre,
                'anyo' => $pago->anyo,
                'pendiente' => $pendiente,
            ];
            $totalPendiente += $pendiente;
        }

        Log::info('AvisarImpagos: ' . count($impagosParaReporte) . ' avisos de impago enviados.');

        if (count($impagosParaReporte) > 0) {
            $destinatario = (string) config('mail.reportes.pagos_to');
            Mail::to($destinatario)->queue(new ReporteImpagosMail(
                collect($impagosParaReporte),
                $totalPendiente
            ));
        }
    }
}
