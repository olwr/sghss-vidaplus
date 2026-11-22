<?php

namespace App\Domains\Agendamentos\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LembreteConsultaMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /** @param array<string, mixed> $dados somente dados primitivos (sem models) */
    public function __construct(public array $dados)
    {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Lembrete de consulta — VidaPlus');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.lembrete-consulta');
    }
}
