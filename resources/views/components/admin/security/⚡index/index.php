<?php

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Sécurité')] class extends Component
{
    public string $code = '';

    public bool $confirmationPending = false;

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    #[Computed]
    public function admin(): AdminUser
    {
        return Auth::guard('admin')->user();
    }

    public function mount(): void
    {
        $this->confirmationPending = $this->admin->two_factor_secret !== null
            && $this->admin->two_factor_confirmed_at === null;
    }

    /**
     * Activation (Fortify `EnableTwoFactorAuthentication`) : secret + codes
     * de récupération générés, QR affiché, confirmation TOTP requise.
     */
    public function enable(): void
    {
        app(EnableTwoFactorAuthentication::class)($this->admin);

        $this->confirmationPending = true;
    }

    /**
     * Confirmation du code : verrouille l'activation (`two_factor_confirmed_at`).
     */
    public function confirm(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! app(TwoFactorAuthenticationProvider::class)->verify(decrypt($this->admin->two_factor_secret), $this->code)) {
            throw ValidationException::withMessages([
                'code' => __('Le code fourni est invalide.'),
            ]);
        }

        $this->admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->confirmationPending = false;
        $this->reset('code');

        Flux::toast(__('Double authentification activée.'));
    }

    public function disable(): void
    {
        app(DisableTwoFactorAuthentication::class)($this->admin);

        Flux::toast(__('Double authentification désactivée.'));
    }

    /** @return array<int, string> */
    public function recoveryCodes(): array
    {
        return $this->admin->recoveryCodes();
    }

    #[Computed]
    public function passkeys(): Collection
    {
        return $this->admin->passkeys()->latest()->get();
    }

    public function deletePasskey(int $id): void
    {
        $passkey = $this->admin->passkeys()->findOrFail($id);

        app(DeletePasskey::class)($this->admin, $passkey);

        Flux::toast(__('Passkey supprimée.'));

        unset($this->passkeys);
    }

    /**
     * Changement du mot de passe personnel : l'ancien mot de passe est exigé,
     * les autres sessions sont révoquées (la session courante est conservée),
     * l'événement est consigné dans l'audit.
     */
    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'password' => ['required', 'string', PasswordRule::default(), 'same:passwordConfirmation'],
        ]);

        if (! Hash::check($this->currentPassword, $this->admin->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => __('Le mot de passe actuel est incorrect.'),
            ]);
        }

        $this->admin->update(['password' => $this->password]);

        DB::table('sessions')
            ->where('user_id', $this->admin->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        AdminAuditLog::record('admin_user.password_changed', ['email' => $this->admin->email]);

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);

        Flux::toast(__('Mot de passe modifié — les autres sessions ont été déconnectées.'));
    }
};
