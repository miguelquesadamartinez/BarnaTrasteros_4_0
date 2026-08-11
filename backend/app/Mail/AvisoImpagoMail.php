<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AvisoImpagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $filas;
    public $totalPendiente;
    public $empresa;

    public function __construct($cliente, $filas, $totalPendiente)
    {
        $this->cliente = $cliente;
        $this->filas = $filas;
        $this->totalPendiente = $totalPendiente;
        $this->empresa = config('empresa');
    }

    public function build()
    {
        $subject = count($this->filas) > 1 ? 'Aviso de pagos pendientes' : 'Aviso de pago pendiente';

        return $this->subject($subject)
            ->view('emails.aviso-impago')
            ->with([
                'cliente' => $this->cliente,
                'filas' => $this->filas,
                'totalPendiente' => $this->totalPendiente,
                'empresa' => $this->empresa,
            ]);
    }
}
