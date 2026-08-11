<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReportePagosPendientesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $pendientes,
        public float $totalPendiente,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte semanal de pagos pendientes - ' . now()->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-pagos-pendientes',
            with: [
                'pendientes' => $this->pendientes,
                'totalPendiente' => $this->totalPendiente,
                'fechaEnvio' => now()->format('d/m/Y H:i'),
                'empresa' => config('empresa'),
            ],
        );
    }
}
