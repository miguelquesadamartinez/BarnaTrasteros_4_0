<?php

namespace App\Jobs;

use App\Mail\ReporteImpagosMail;
use App\Models\Cliente;
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
        // Clientes con al menos un pago que ya cumple los 5 días de margen y no se ha avisado aún.
        $clienteIds = PagoAlquiler::whereIn('estado', ['pendiente', 'parcial'])
            ->whereNull('aviso_impago_enviado_at')
            ->get()
            ->filter(function (PagoAlquiler $pago) {
                $fechaAviso = Carbon::create($pago->anyo, $pago->mes, 5)->addDays(self::DIAS_MARGEN);
                return Carbon::now()->gte($fechaAviso);
            })
            ->pluck('cliente_id')
            ->unique();

        $impagosParaReporte = [];
        $totalPendiente = 0.0;
        $clientesAvisados = 0;

        foreach ($clienteIds as $clienteId) {
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                continue;
            }

            // Todos los pagos pendientes del cliente (para el reporte interno), antes de marcarlos avisados.
            $pendientes = $cliente->pagosAlquiler()->whereIn('estado', ['pendiente', 'parcial'])->get();

            if (!$cliente->enviarAvisoImpago()) {
                Log::info("AvisarImpagos: cliente {$clienteId} sin email, se omite.");
                continue;
            }
            $clientesAvisados++;

            foreach ($pendientes as $pago) {
                $pendiente = max(0, (float) $pago->importe_total - (float) $pago->pagado);
                $impagosParaReporte[] = [
                    'cliente_nombre' => trim("{$cliente->nombre} {$cliente->apellido}"),
                    'cliente_dni' => $cliente->dni,
                    'tipo' => $pago->tipo,
                    'numero' => $pago->numero ?? $pago->referencia_id,
                    'mesNombre' => ucfirst(Carbon::create()->month($pago->mes)->locale('es')->monthName),
                    'anyo' => $pago->anyo,
                    'pendiente' => $pendiente,
                ];
                $totalPendiente += $pendiente;
            }
        }

        Log::info("AvisarImpagos: avisos enviados a {$clientesAvisados} clientes.");

        if (count($impagosParaReporte) > 0) {
            $destinatario = (string) config('mail.reportes.pagos_to');
            Mail::to($destinatario)->queue(new ReporteImpagosMail(
                collect($impagosParaReporte),
                $totalPendiente
            ));
        }
    }
}
