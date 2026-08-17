<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CambioPrecioMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $cambios,
        public string $motivo,
    ) {
    }

    public function envelope(): Envelope
    {
        $titulo = $this->cambios->count() > 1 ? 'Revisión de precios aplicada' : 'Cambio de precio aplicado';

        return new Envelope(subject: $titulo);
    }

    public function content(): Content
    {
        $totalAnterior = $this->cambios->sum(fn ($c) => (float) $c->precio_anterior);
        $totalNuevo = $this->cambios->sum(fn ($c) => (float) $c->precio_nuevo);

        return new Content(
            view: 'emails.cambio-precio',
            with: [
                'cambios' => $this->cambios,
                'motivo' => $this->motivo,
                'totalAnterior' => $totalAnterior,
                'totalNuevo' => $totalNuevo,
                'fechaEnvio' => now()->format('d/m/Y H:i'),
                'empresa' => config('empresa'),
            ],
        );
    }
}
