<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupDatabaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $filepath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Backup de la base de datos - ' . now()->format('d/m/Y H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup-database',
            with: [
                'filename' => basename($this->filepath),
                'fecha'    => now()->format('d/m/Y H:i:s'),
                'sizeKb'   => round(filesize($this->filepath) / 1024, 1),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filepath),
        ];
    }
}
