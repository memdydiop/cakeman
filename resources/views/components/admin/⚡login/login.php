<?php

use App\Models\AdminLoginLog;
use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Console Cakeman'])] #[Title('Console Cakeman')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public string $code = '';

    public ?int $pendingAdminId = null;

    /**
     * Connexion console (guard `admin`, schéma central) — une désactivation
     * (`is_active = false`) révoque l'accès (CdC § 12). Si l'utilisateur a
     * activé la double authentification, un challenge TOTP est exigé avant
     * l'ouverture de session. Verrouillage par tentatives (RateLimiter) et
     * 2FA obligatoire pilotés par la Configuration.
     */
    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'admin-login|'.Str::lower($this->email).'|'.request()->ip();

        if (! RateLimiter::attempt($key, 5, fn (): bool => true)) {
            throw ValidationException::withMessages([
                'email' => __('Trop de tentatives. Réessayez dans :secondes secondes.', [
                    'secondes' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        $admin = AdminUser::where('email', $this->email)->first();

        if (! $admin || ! Auth::guard('admin')->validate(['email' => $this->email, 'password' => $this->password])) {
            $this->logAttempt(email: $this->email, adminUserId: $admin?->id, success: false);

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants sont incorrects.'),
            ]);
        }

        if (Setting::get('security.two_factor_required', false) === '1' && $admin->two_factor_confirmed_at === null) {
            $this->logAttempt(email: $this->email, adminUserId: $admin->id, success: false);

            throw ValidationException::withMessages([
                'email' => __('La double authentification est obligatoire : configurez-la sur la page Sécurité, puis reconnectez-vous.'),
            ]);
        }

        if ($admin->two_factor_secret !== null) {
            $this->pendingAdminId = $admin->id;
            $this->code = '';

            return;
        }

        $this->completeLogin($admin, twoFactor: false);
    }

    /**
     * Vérification du code TOTP (même moteur Fortify que les tenants).
     */
    public function confirmChallenge(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $admin = AdminUser::findOrFail($this->pendingAdminId);

        if (! app(TwoFactorAuthenticationProvider::class)->verify(decrypt($admin->two_factor_secret), $this->code)) {
            $this->logAttempt(email: $admin->email, adminUserId: $admin->id, success: false, twoFactor: true);

            throw ValidationException::withMessages([
                'code' => __('Le code fourni est invalide.'),
            ]);
        }

        $this->completeLogin($admin, twoFactor: true);
    }

    private function completeLogin(AdminUser $admin, bool $twoFactor = false): void
    {
        Auth::guard('admin')->login($admin, $this->remember);

        if (! $admin->canAccessConsole()) {
            Auth::guard('admin')->logout();

            $this->logAttempt(email: $admin->email, adminUserId: $admin->id, success: false, twoFactor: $twoFactor);

            throw ValidationException::withMessages([
                'email' => __('Votre compte est désactivé. Contactez un administrateur.'),
            ]);
        }

        $admin->update(['last_login_at' => now()]);

        session()->regenerate();

        $this->logAttempt(email: $admin->email, adminUserId: $admin->id, success: true, twoFactor: $twoFactor);

        $this->redirect(route('admin.dashboard'));
    }

    private function logAttempt(string $email, ?int $adminUserId, bool $success, bool $twoFactor = false): void
    {
        AdminLoginLog::create([
            'admin_user_id' => $adminUserId,
            'email' => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'success' => $success,
            'two_factor' => $twoFactor,
        ]);
    }
};
