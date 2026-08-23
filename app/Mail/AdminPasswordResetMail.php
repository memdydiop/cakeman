<?php

namespace App\Mail;

use App\Models\AdminUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Réinitialisation du mot de passe d'un utilisateur console, déclenchée par
 * un administrateur (broker `admins`, lien valable 60 minutes).
 */
class AdminPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdminUser $user,
        public string $token,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe console',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.password-reset',
            with: [
                'resetUrl' => route('admin.password.reset', ['token' => $this->token]),
                'email' => $this->user->email,
            ],
        );
    }
}
