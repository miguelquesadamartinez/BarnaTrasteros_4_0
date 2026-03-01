<?php

namespace App\Jobs;

use App\Models\PagoAlquiler;
use App\Models\Piso;
use App\Models\Trastero;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Generar pagos para trasteros alquilados
        $trasteros = Trastero::whereNotNull('cliente_id')->get();
        foreach ($trasteros as $trastero) {
            $existe = PagoAlquiler::where('tipo', 'trastero')
                ->where('referencia_id', $trastero->id)
                ->where('mes', $this->mes)
                ->where('anyo', $this->anyo)
                ->exists();

            if (!$existe) {
                PagoAlquiler::create([
                    'cliente_id'    => $trastero->cliente_id,
                    'tipo'          => 'trastero',
                    'referencia_id' => $trastero->id,
                    'mes'           => $this->mes,
                    'anyo'          => $this->anyo,
                    'importe_total' => $trastero->precio_mensual,
                    'pagado'        => 0,
                    'estado'        => 'pendiente',
                ]);
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
                PagoAlquiler::create([
                    'cliente_id'    => $piso->cliente_id,
                    'tipo'          => 'piso',
                    'referencia_id' => $piso->id,
                    'mes'           => $this->mes,
                    'anyo'          => $this->anyo,
                    'importe_total' => $piso->precio_mensual,
                    'pagado'        => 0,
                    'estado'        => 'pendiente',
                ]);
                $generados++;
            }
        }

        Log::info("GenerarPagosMensuales: {$generados} registros generados para {$this->mes}/{$this->anyo}");
    }
}
