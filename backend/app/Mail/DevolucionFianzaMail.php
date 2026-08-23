<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DevolucionFianzaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $fianza;
    public $pdf;

    /**
     * @param $cliente array
     * @param $fianza array
     * @param $pdf string
     */
    public function __construct($cliente, $fianza, $pdf)
    {
        $this->cliente = $cliente;
        $this->fianza = $fianza;
        $this->pdf = base64_encode($pdf);
    }

    public function build()
    {
        \Carbon\Carbon::setLocale('es');
        $nombreCliente = trim(($this->cliente['nombre'] ?? '') . '_' . ($this->cliente['apellido'] ?? ''), '_');
        $nombreCliente = preg_replace('/\s+/', '_', $nombreCliente) ?: $this->fianza['id'];
        $nombreArchivo = sprintf('Devolucion_fianza_%s.pdf', $nombreCliente);

        return $this->subject('Comprobante de devolución de fianza')
            ->view('emails.devolucion-fianza')
            ->with([
                'cliente' => $this->cliente,
                'fianza' => $this->fianza,
            ])
            ->attachData(base64_decode($this->pdf), $nombreArchivo, [
                'mime' => 'application/pdf',
            ]);
    }
}
