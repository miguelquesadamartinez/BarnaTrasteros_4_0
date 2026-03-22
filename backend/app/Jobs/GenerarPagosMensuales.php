<?php

namespace App\Jobs;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // Generar pagos para trasteros alquilados
        $trasteros = Trastero::whereNotNull('cliente_id')->get();
        foreach ($trasteros as $trastero) {
            $existe = PagoAlquiler::where('tipo', 'trastero')
                ->where('referencia_id', $trastero->id)
                ->where('mes', $this->mes)
                ->where('anyo', $this->anyo)
                ->exists();

            if (!$existe) {
                $pago = PagoAlquiler::create([
                    'cliente_id'    => $trastero->cliente_id,
                    'tipo'          => 'trastero',
                    'referencia_id' => $trastero->id,
                    'numero'        => $trastero->numero,
                    'mes'           => $this->mes,
                    'anyo'          => $this->anyo,
                    'importe_total' => $trastero->precio_mensual,
                    'pagado'        => 0,
                    'estado'        => 'pendiente',
                ]);
                $pagosGeneradosIds[] = $pago->id;
                $generados++;
            }
        }

        // Generar pagos para pisos alquilados
        $pisos = Piso::whereNotNull('cliente_id')->get();
        foreach ($pisos as $piso) {
            $existe = PagoAlquiler::where('tipo', 'piso')
                ->where('referencia_id', $piso->id)
                ->where('mes', $this->mes)
                ->where('anyo', $this->anyo)
                ->exists();

            if (!$existe) {
                $pago = PagoAlquiler::create([
                    'cliente_id'    => $piso->cliente_id,
                    'tipo'          => 'piso',
                    'referencia_id' => $piso->id,
                    'numero'        => $piso->numero,
                    'mes'           => $this->mes,
                    'anyo'          => $this->anyo,
                    'importe_total' => $piso->precio_mensual,
                    'pagado'        => 0,
                    'estado'        => 'pendiente',
                ]);
                $pagosGeneradosIds[] = $pago->id;
                $generados++;
            }
        }

        Cache::tags(['pagos-alquiler'])->flush();

        $pagosGenerados = PagoAlquiler::with('cliente')
            ->whereIn('id', $pagosGeneradosIds)
            ->orderBy('tipo')
            ->orderBy('numero')
            ->get();

        $totalImporte = (float) $pagosGenerados->sum(function (PagoAlquiler $pago): float {
            return (float) $pago->importe_total;
        });

        $destinatario = (string) config('mail.reportes.pagos_to', 'miguel.quesada.martinez.1975@gmail.com');

        if (env('APP_ENV') === 'production') {
            Mail::to($destinatario)
                ->cc('nieves.martinez.lloret@hotmail.es')
                ->queue(
                new ReportePagosGeneradosMail($this->mes, $this->anyo, $pagosGenerados, $totalImporte)
            );
        } else {
            Mail::to($destinatario)
                ->queue(
                new ReportePagosGeneradosMail($this->mes, $this->anyo, $pagosGenerados, $totalImporte)
            );
        }
        Log::info(
            "GenerarPagosMensuales: Reporte encolado para {$destinatario} con {$pagosGenerados->count()} pagos (total {$totalImporte})"
        );


        Log::info("GenerarPagosMensuales: {$generados} registros generados para {$this->mes}/{$this->anyo}");
    }
}
