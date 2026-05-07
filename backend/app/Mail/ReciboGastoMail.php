<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReciboGastoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $gasto;
    public $detalle;
    public $pdf;
    public $esDetalle;

    /**
     * @param array      $gasto
     * @param string     $pdf       Binary PDF content
     * @param array|null $detalle
     */
    public function __construct(array $gasto, string $pdf, ?array $detalle = null)
    {
        $this->gasto     = $gasto;
        $this->pdf       = base64_encode($pdf);
        $this->detalle   = $detalle;
        $this->esDetalle = $detalle !== null;
    }

    public function build()
    {
        if ($this->esDetalle) {
            $nombreRecibo = sprintf('Recibo_gasto_%d_pago_%d.pdf', $this->gasto['id'], $this->detalle['id']);
            $subject      = 'Recibo de pago — ' . $this->gasto['descripcion'];
        } else {
            $nombreRecibo = sprintf('Recibo_gasto_%d.pdf', $this->gasto['id']);
            $subject      = 'Recibo de gasto — ' . $this->gasto['descripcion'];
        }

        return $this->subject($subject)
            ->view('emails.recibo-gasto')
            ->with([
                'gasto'     => $this->gasto,
                'detalle'   => $this->detalle,
                'esDetalle' => $this->esDetalle,
            ])
            ->attachData(base64_decode($this->pdf), $nombreRecibo, [
                'mime' => 'application/pdf',
            ]);
    }
}
