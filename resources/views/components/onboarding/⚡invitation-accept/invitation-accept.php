<?php

use App\Enums\TenantStatus;
use App\Models\TenantUserInvitation;
use App\Models\User;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Activer ma boutique'])] #[Title('Activer ma boutique')] class extends Component
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
    public function invitation(): ?TenantUserInvitation
    {
        return TenantUserInvitation::where('token', $this->token)->first();
    }

    /**
     * Une invitation ne peut être acceptée que pour une boutique en attente
     * d'activation (onboarding) ou disposant déjà d'un gérant actif
     * (remplacement, transfert de propriété). Une boutique suspendue conserve
     * son gérant : l'acceptation n'en change pas le statut.
     */
    #[Computed]
    public function canAccept(): bool
    {
        $status = tenant()->status;

        return $status === TenantStatus::Pending
            || ($status !== TenantStatus::Cancelled && tenant()->hasValidManager());
    }

    /**
     * Acceptation de l'invitation gérant (plan-sprint0 § 5.5) : création du
     * compte Gérant, activation de la boutique (`pending` → `active`) et
     * connexion directe au tableau de bord. Une invitation de remplacement
     * désactive le gérant précédent sans changer le statut de la boutique.
     */
    public function accept(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'string', 'same:password'],
        ]);

        $invitation = TenantUserInvitation::where('token', $this->token)->first();

        if (! $invitation?->isValid() || ! $this->canAccept) {
            throw ValidationException::withMessages([
                'token' => __('Ce lien est invalide ou a expiré.'),
            ]);
        }

        $userLimit = tenant()->planLimits()['users'] ?? null;

        // En onboarding (`pending`), l'utilisateur est nouveau : la limite du
        // plan s'applique. En remplacement, l'ancien gérant est désactivé au
        // même moment : l'effectif ne croît pas, la limite ne s'applique pas.
        if (tenant()->status === TenantStatus::Pending
            && $userLimit !== null
            && User::count() >= $userLimit) {
            throw ValidationException::withMessages([
                'password' => __('La limite d\'utilisateurs de votre plan est atteinte.'),
            ]);
        }

        $user = User::create([
            'name' => $invitation->name,
            'email' => $invitation->email,
            'password' => $this->password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole(TenantRolesSeeder::ROLE_MANAGER);

        // Un seul gérant actif par boutique : l'acceptation d'une invitation
        // de remplacement désactive le gérant précédent (transfert de
        // propriété, plan-sprint0 § 5.5).
        User::role(TenantRolesSeeder::ROLE_MANAGER)
            ->whereKeyNot($user->id)
            ->update(['is_active' => false]);

        $invitation->update(['accepted_at' => now()]);

        if (tenant()->status === TenantStatus::Pending) {
            tenant()->update(['status' => TenantStatus::Active]);
        }

        Auth::login($user);

        $this->redirect(route('dashboard'));
    }
};
