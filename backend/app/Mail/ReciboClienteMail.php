<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReciboClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $mes;
    public $anyo;
    public $pago;
    public $detalle; // Puede ser null si es recibo total
    public $importe_total;
    public $pdf;
    public $esDetalle;

    /**
     * @param $cliente array
     * @param $mes int
     * @param $anyo int
     * @param $pago array
     * @param $importe_total float
     * @param $pdf string
     * @param $detalle array|null
     */
    public function __construct($cliente, $mes, $anyo, $pago, $importe_total, $pdf, $detalle = null)
    {
        $this->cliente = $cliente;
        $this->mes = $mes;
        $this->anyo = $anyo;
        $this->pago = $pago;
        $this->importe_total = $importe_total;
        $this->pdf = $pdf;
        $this->detalle = $detalle;
        $this->esDetalle = $detalle !== null;
    }

    public function build()
    {
        \Carbon\Carbon::setLocale('es');
        $mesNombre = ucfirst(\Carbon\Carbon::create()->month($this->mes)->locale('es')->monthName);
        if ($this->esDetalle) {
            $nombreRecibo = sprintf('Recibo_pago_%d_detalle_%d.pdf', $this->pago['id'], $this->detalle['id']);
            $subject = "Recibo del pago de $mesNombre $this->anyo";
            $view = 'emails.recibo-pago';
        } else {
            $nombreRecibo = sprintf('Recibo_pago_%d.pdf', $this->pago['id']);
            $subject = "Recibo mensual de $mesNombre $this->anyo";
            $view = 'emails.recibo-pago-total';
        }
        return $this->subject($subject)
            ->view($view)
            ->with([
                'pago' => $this->pago,
                'detalle' => $this->detalle,
                'cliente' => $this->cliente,
                'mes' => $this->mes,
                'anyo' => $this->anyo,
                'mesNombre' => $mesNombre,
                'importe_total' => $this->importe_total,
            ])
            ->attachData($this->pdf, $nombreRecibo, [
                'mime' => 'application/pdf',
            ]);
    }
}
