<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReportePagosGeneradosMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $mes,
        public int $anyo,
        public Collection $pagos,
        public float $totalImporte,
    ) {
    }

    public function envelope(): Envelope
    {
        $mesNombre = ucfirst(Carbon::create($this->anyo, $this->mes, 1)->locale('es')->translatedFormat('F'));

        return new Envelope(
            subject: "Reporte de pagos generados - {$mesNombre} {$this->anyo}",
        );
    }

    public function content(): Content
    {
        $mesNombre = ucfirst(Carbon::create($this->anyo, $this->mes, 1)->locale('es')->translatedFormat('F'));

        return new Content(
            view: 'emails.reporte-pagos-generados',
            with: [
                'mes' => $this->mes,
                'anyo' => $this->anyo,
                'mesNombre' => $mesNombre,
                'fechaEnvio' => now()->format('d/m/Y H:i'),
                'pagos' => $this->pagos,
                'totalImporte' => $this->totalImporte,
                'totalRegistros' => $this->pagos->count(),
                'totalTrasteros' => $this->pagos->where('tipo', 'trastero')->count(),
                'totalPisos' => $this->pagos->where('tipo', 'piso')->count(),
            ],
        );
    }
}
