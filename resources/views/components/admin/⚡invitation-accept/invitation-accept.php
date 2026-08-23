<?php

use App\Models\AdminUser;
use App\Models\AdminUserInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Activer mon compte'])] #[Title('Activer mon compte')] class extends Component
{
    public string $token = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Invitation liée au lien : une invitation consommée (`accepted_at` non
     * nul) ou expirée n'est plus valide — le formulaire est alors masqué.
     */
    #[Computed]
    public function invitation(): ?AdminUserInvitation
    {
        return AdminUserInvitation::where('token', $this->token)->first();
    }

    /**
     * Acceptation d'une invitation (lien one-time, plan-sprint0 § 5.8) :
     * pose du mot de passe → compte activé → connexion console.
     */
    public function accept(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'string', 'same:password'],
        ]);

        $invitation = AdminUserInvitation::where('token', $this->token)->first();

        if (! $invitation?->isValid()) {
            throw ValidationException::withMessages([
                'token' => __('Ce lien est invalide ou a expiré.'),
            ]);
        }

        $admin = AdminUser::where('email', $invitation->email)->firstOrFail();

        $admin->update([
            'password' => $this->password,
            'is_active' => true,
        ]);

        $admin->assignRole($invitation->role);

        $invitation->update(['accepted_at' => now()]);

        Auth::guard('admin')->login($admin);

        $this->redirect(route('admin.dashboard'));
    }
};
