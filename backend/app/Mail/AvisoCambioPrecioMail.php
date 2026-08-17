<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AvisoCambioPrecioMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $cliente,
        public Collection $filas,
    ) {
    }

    public function envelope(): Envelope
    {
        $titulo = $this->filas->count() > 1
            ? 'Actualización de precios de tus alquileres'
            : 'Actualización del precio de tu alquiler';

        return new Envelope(subject: $titulo);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.aviso-cambio-precio',
            with: [
                'cliente' => $this->cliente,
                'filas' => $this->filas,
                'empresa' => config('empresa'),
            ],
        );
    }
}
