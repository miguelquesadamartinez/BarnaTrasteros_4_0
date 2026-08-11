<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AvisoImpagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $pago;
    public $mesNombre;
    public $pendiente;
    public $empresa;

    public function __construct($cliente, $pago, $mesNombre, $pendiente)
    {
        $this->cliente = $cliente;
        $this->pago = $pago;
        $this->mesNombre = $mesNombre;
        $this->pendiente = $pendiente;
        $this->empresa = config('empresa');
    }

    public function build()
    {
        return $this->subject("Aviso de pago pendiente - {$this->mesNombre} {$this->pago['anyo']}")
            ->view('emails.aviso-impago')
            ->with([
                'cliente' => $this->cliente,
                'pago' => $this->pago,
                'mesNombre' => $this->mesNombre,
                'pendiente' => $this->pendiente,
                'empresa' => $this->empresa,
            ]);
    }
}
