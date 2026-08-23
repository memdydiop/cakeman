<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification d'un nouveau lead de la landing (Module Configuration,
 * onglet Acquisition) — envoyé à l'e-mail de la console si configuré.
 */
class NewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau lead — '.($this->lead->name ?? 'Cakeman'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.leads.new',
            with: [
                'lead' => $this->lead,
            ],
        );
    }
}
