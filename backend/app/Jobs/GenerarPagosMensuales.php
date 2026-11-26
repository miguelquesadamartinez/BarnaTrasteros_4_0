<?php

namespace App\Jobs;

use App\Http\Controllers\Api\Concerns\GeneraPagoAlAsignar;
use App\Mail\ReportePagosGeneradosMail;
use App\Models\PagoAlquiler;
use App\Models\Piso;
use App\Models\Trastero;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerarPagosMensuales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GeneraPagoAlAsignar;

    public int $mes;
    public int $anyo;

    public function __construct(?int $mes = null, ?int $anyo = null)
    {
        $ahora = Carbon::now();
        $this->mes  = $mes  ?? $ahora->month;
        $this->anyo = $anyo ?? $ahora->year;
    }

    public function handle(): void
    {
        Log::info("GenerarPagosMensuales: Generando pagos para {$this->mes}/{$this->anyo}");

        $generados = 0;
        $pagosGeneradosIds = [];

        $inicioMes = Carbon::create($this->anyo, $this->mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth()->startOfDay();

        $unidades = [
            'trastero' => Trastero::whereNotNull('cliente_id')->get(),
            'piso'     => Piso::whereNotNull('cliente_id')->get(),
        ];

        foreach ($unidades as $tipo => $lista) {
            foreach ($lista as $unidad) {
                // Se usan el inquilino y el precio actuales, así que no tiene sentido
                // cobrar un mes que terminó antes de que empezara el alquiler.
                $fechaInicio = $unidad->fecha_inicio_alquiler
                    ? Carbon::parse($unidad->fecha_inicio_alquiler)->startOfDay()
                    : null;
                if ($fechaInicio && $fechaInicio->greaterThan($finMes)) {
                    continue;
                }

                $existe = PagoAlquiler::where('tipo', $tipo)
                    ->where('referencia_id', $unidad->id)
                    ->where('cliente_id', $unidad->cliente_id)
                    ->where('mes', $this->mes)
                    ->where('anyo', $this->anyo)
                    ->exists();

                if ($existe) {
                    continue;
                }

                // Si el alquiler empezó a mitad de ese mes, se cobra la parte proporcional.
                $importe = ($fechaInicio && $fechaInicio->greaterThan($inicioMes))
                    ? $this->calcularProrrateo((float) $unidad->precio_mensual, $fechaInicio, $finMes, $this->mes, $this->anyo)
                    : $unidad->precio_mensual;

                $pago = PagoAlquiler::create([
                    'cliente_id'    => $unidad->cliente_id,
                    'tipo'          => $tipo,
                    'referencia_id' => $unidad->id,
                    'numero'        => $unidad->numero,
                    'mes'           => $this->mes,
                    'anyo'          => $this->anyo,
                    'importe_total' => $importe,
                    'pagado'        => 0,
                    'estado'        => 'pendiente',
                ]);
                $pagosGeneradosIds[] = $pago->id;
                $generados++;
            }
        }

        Cache::tags(['pagos-alquiler', 'relatorio', 'facturas'])->flush();

        $pagosGenerados = PagoAlquiler::with('cliente')
            ->whereIn('id', $pagosGeneradosIds)
            ->orderBy('tipo')
            ->orderBy('numero')
            ->get();

        $totalImporte = (float) $pagosGenerados->sum(function (PagoAlquiler $pago): float {
            return (float) $pago->importe_total;
        });

        if ($generados > 0) {
            $destinatario = (string) config('mail.reportes.pagos_to', 'miguel.quesada.martinez.1975@gmail.com');
            $cc = config('mail.reportes.pagos_cc');

            $mail = Mail::to($destinatario);
            if (config('app.env') === 'production' && !empty($cc)) {
                $mail->cc($cc);
            }
            $mail->queue(new ReportePagosGeneradosMail($this->mes, $this->anyo, $pagosGenerados, $totalImporte));

            Log::info(
                "GenerarPagosMensuales: Reporte encolado para {$destinatario} con {$pagosGenerados->count()} pagos (total {$totalImporte})"
            );
        } else {
            Log::info("GenerarPagosMensuales: No se han generado pagos, no se envía email.");
        }


        Log::info("GenerarPagosMensuales: {$generados} registros generados para {$this->mes}/{$this->anyo}");
    }
}
