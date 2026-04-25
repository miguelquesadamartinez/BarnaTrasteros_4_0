<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class FacturaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $mes;
    public $anyo;
    public $pagos;
    public $importe_total;
    public $pdf;

    public function __construct($cliente, $mes, $anyo, $pagos, $importe_total
    , $pdf
    )
    {
        $this->cliente = $cliente;
        $this->mes = $mes;
        $this->anyo = $anyo;
        $this->pagos = $pagos;
        $this->importe_total = $importe_total;
        $this->pdf = $pdf;
    }

    public function build()
    {
        // Usar Carbon para obtener el nombre del mes en español
        \Carbon\Carbon::setLocale('es');
        $mesNombre = ucfirst(\Carbon\Carbon::create()->month($this->mes)->locale('es')->monthName);
        $nombreFactura = sprintf('Factura_%s-%02d-%04d.pdf', $this->anyo, $this->mes, $this->cliente['id']);
        return $this->subject("Factura del mes de $mesNombre $this->anyo")
            ->view('emails.factura-cliente')
            ->with([
                'cliente' => $this->cliente,
                'mes' => $this->mes,
                'anyo' => $this->anyo,
                'mesNombre' => $mesNombre,
                'pagos' => $this->pagos,
                'importe_total' => $this->importe_total,
            ])
            ->attachData($this->pdf, $nombreFactura, [
                'mime' => 'application/pdf',
            ])
            ;
    }
}
