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
 * Relance d'échéance d'abonnement (Module 12) : envoyée au gérant J-7/J-3/J-1
 * avant la fin de période. Contenu 100 % scalaire (file d'attente sûre).
 */
class BillingReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $managerName,
        public string $shopName,
        public string $planName,
        public int $amount,
        public CarbonInterface $dueAt,
        public int $daysLeft,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre abonnement Cakeman arrive à échéance',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.billing.reminder',
            with: [
                'managerName' => $this->managerName,
                'shopName' => $this->shopName,
                'planName' => $this->planName,
                'amount' => $this->amount,
                'dueAt' => $this->dueAt,
                'daysLeft' => $this->daysLeft,
            ],
        );
    }
}
