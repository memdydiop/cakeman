<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation à activer une boutique (plan-sprint0 § 5.5) : lien one-time
 * valable 48 h, envoyé au gérant lors de la création depuis la console.
 * File d'attente : le contenu n'utilise que des scalaires, aucun contexte
 * tenant requis côté worker.
 */
class ManagerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $managerName,
        public string $shopName,
        public string $acceptUrl,
        public CarbonInterface $expiresAt,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à activer votre boutique Cakeman',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tenant.invitation',
            with: [
                'managerName' => $this->managerName,
                'shopName' => $this->shopName,
                'acceptUrl' => $this->acceptUrl,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
