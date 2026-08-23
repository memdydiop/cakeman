<?php

namespace App\Mail;

use App\Models\AdminUserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation à rejoindre la console plateforme (CdC § 12) — lien one-time
 * valable 48 h (plan-sprint0 § 5.8).
 */
class AdminInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdminUserInvitation $invitation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à rejoindre la console Cakeman',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.invitation',
            with: [
                'acceptUrl' => route('admin.invitations.accept', ['token' => $this->invitation->token]),
                'role' => $this->invitation->role,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
