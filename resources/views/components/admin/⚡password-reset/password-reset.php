<?php

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth', ['title' => 'Nouveau mot de passe'])] #[Title('Nouveau mot de passe')] class extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    /**
     * E-mail associé au token : nécessaire pour le rapprochement du compte
     * (le token n'encode pas l'utilisateur).
     */
    #[Computed]
    public function resetEmail(): ?string
    {
        return (string) DB::table('admin_password_reset_tokens')->where('token', $this->token)->value('email') ?: null;
    }

    /**
     * Pose d'un nouveau mot de passe via le broker `admins` (broker central,
     * table `admin_password_reset_tokens`).
     */
    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::default(), 'same:passwordConfirmation'],
        ]);

        $status = Password::broker('admins')->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
                'token' => $this->token,
            ],
            function (AdminUser $user, string $password): void {
                $user->update(['password' => Hash::make($password)]);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __('Ce lien est invalide, a expiré ou l\'e-mail ne correspond pas.'),
            ]);
        }

        $this->redirect(route('admin.login'));
    }
};
