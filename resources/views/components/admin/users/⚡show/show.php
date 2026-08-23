<?php

use App\Enums\AdminPermission;
use App\Mail\AdminPasswordResetMail;
use App\Models\AdminAuditLog;
use App\Models\AdminLoginLog;
use App\Models\AdminUser;
use App\Models\AdminUserInvitation;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts.admin')] #[Title('Utilisateur')] class extends Component
{
    use WithPagination;

    public int $userId = 0;

    public function mount(int $user): void
    {
        $this->userId = $user;
    }

    #[Computed]
    public function user(): AdminUser
    {
        return AdminUser::with(['roles', 'permissions', 'passkeys'])->findOrFail($this->userId);
    }

    /**
     * Sessions actives de l'utilisateur (table `sessions`, schéma central).
     *
     * @return list<array{id: string, ip_address: string|null, user_agent: string|null, last_activity: Carbon}>
     */
    #[Computed]
    public function sessions(): array
    {
        return DB::table('sessions')
            ->where('user_id', $this->userId)
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get()
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => Carbon::createFromTimestamp($session->last_activity),
            ])
            ->all();
    }

    /**
     * Audit ciblé sur cet utilisateur (CdC § 12) : les actions qu'il a
     * réalisées (`admin_user_id`) et celles le concernant (propriétés).
     */
    #[Computed]
    public function auditLogs(): LengthAwarePaginator
    {
        $email = $this->user->email;

        return AdminAuditLog::query()
            ->with('tenant')
            ->where(function ($query) use ($email): void {
                $query->where('admin_user_id', $this->userId)
                    ->orWhereRaw('properties::text ilike ?', ['%"'.$email.'"%']);
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    #[Computed]
    public function pendingInvitation(): ?AdminUserInvitation
    {
        return AdminUserInvitation::where('email', $this->user->email)
            ->whereNull('accepted_at')
            ->latest()
            ->first();
    }

    #[Computed]
    public function recentLogins(): Collection
    {
        return AdminLoginLog::where('admin_user_id', $this->userId)
            ->orWhere('email', $this->user->email)
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    public function exportGdpr(): StreamedResponse
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $user = $this->user;
        $filename = 'rgpd-'.Str::slug($user->email).'-'.now()->format('Ymd-His').'.json';

        $payload = [
            'user' => $user->only(['id', 'name', 'email', 'phone', 'is_active', 'email_verified_at', 'last_login_at', 'two_factor_confirmed_at', 'created_at']),
            'roles' => $user->roles->pluck('name')->all(),
            'permissions' => $user->permissions->pluck('name')->all(),
            'passkeys' => $user->passkeys->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'created_at' => $p->created_at, 'last_used_at' => $p->last_used_at])->all(),
            'sessions' => $this->sessions,
            'recent_logins' => $this->recentLogins->map(fn ($l) => $l->only(['email', 'ip_address', 'user_agent', 'success', 'two_factor', 'created_at']))->all(),
            'audit' => $this->auditLogs->items(),
            'exported_at' => now()->toIso8601String(),
        ];

        AdminAuditLog::record('admin_user.gdpr_exported', ['email' => $user->email]);

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Désactivation / réactivation — la désactivation révoque les sessions
     * actives (CdC § 12) et le compte ne peut plus se connecter.
     */
    public function toggleActive(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $user = $this->user;

        if ($user->id === auth('admin')->id() && $user->is_active) {
            throw ValidationException::withMessages([
                'user' => __('Vous ne pouvez pas désactiver votre propre compte.'),
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->update(['is_active' => ! $user->is_active]);

            if (! $user->fresh()->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        AdminAuditLog::record(
            'admin_user.updated',
            ['is_active' => $user->fresh()->is_active, 'email' => $user->email],
        );

        Flux::toast($user->fresh()->is_active ? __('Compte réactivé.') : __('Compte désactivé et sessions révoquées.'));
    }

    /**
     * Réinitialisation du mot de passe (forcée par un administrateur) : un
     * lien one-time est envoyé par e-mail (broker `admins`, 60 min).
     */
    public function sendPasswordReset(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $user = $this->user;

        $status = Password::broker('admins')->sendResetLink(
            ['email' => $user->email],
            fn (AdminUser $user, string $token) => Mail::to($user->email)->send(new AdminPasswordResetMail($user, $token)),
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'user' => __('Impossible d\'envoyer le lien de réinitialisation.'),
            ]);
        }

        AdminAuditLog::record('admin_user.password_reset_sent', ['email' => $user->email]);

        Flux::toast(__('Lien de réinitialisation envoyé à :email.', ['email' => $user->email]));
    }

    /**
     * Suppression d'un utilisateur (RGPD / nettoyage) : réservée aux comptes
     * désactivés, audit conservé, sessions et invitations en attente purgées,
     * interdiction de supprimer son propre compte.
     */
    public function deleteUser(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::UserManage->value, 'admin'), 403);

        $user = $this->user;

        if ($user->id === auth('admin')->id()) {
            throw ValidationException::withMessages([
                'user' => __('Vous ne pouvez pas supprimer votre propre compte.'),
            ]);
        }

        if ($user->is_active) {
            throw ValidationException::withMessages([
                'user' => __('Un compte actif ne peut pas être supprimé — désactivez-le d\'abord.'),
            ]);
        }

        DB::transaction(function () use ($user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AdminUserInvitation::where('email', $user->email)->whereNull('accepted_at')->delete();

            AdminAuditLog::record('admin_user.deleted', ['email' => $user->email]);

            $user->delete();
        });

        Flux::toast(__('Utilisateur supprimé.'));

        $this->redirect(route('admin.users.index'));
    }

    public function actionLabel(string $action): string
    {
        return match ($action) {
            'admin_user.invited' => 'Invité',
            'admin_user.updated' => 'Compte modifié',
            'admin_user.deleted' => 'Supprimé',
            'admin_user.invitation_resent' => 'Invitation renvoyée',
            'admin_user.invitation_revoked' => 'Invitation révoquée',
            'admin_user.password_reset_sent' => 'Lien de réinitialisation envoyé',
            'admin_user.password_changed' => 'Mot de passe modifié',
            'admin_user.gdpr_exported' => 'Export RGPD',
            'admin_user.exported' => 'Export des utilisateurs',
            'tenant.created' => 'Boutique créée',
            'tenant.status_changed' => 'Statut de boutique modifié',
            'tenant.invitation_sent' => 'Invitation gérant envoyée',
            'tenant.invitation_resent' => 'Invitation gérant renvoyée',
            'tenant.invitation_revoked' => 'Invitation gérant révoquée',
            'tenant.deleted' => 'Boutique supprimée',
            'tenant.trial_extended' => 'Essai prolongé',
            'tenant.cancelled' => 'Boutique annulée (archivée)',
            'settings.updated' => 'Configuration modifiée',
            'support.session_started' => 'Session support ouverte',
            'support.session_ended' => 'Session support terminée',
            default => $action,
        };
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function details(array $properties): string
    {
        return implode(', ', array_map(
            fn (string $key, mixed $value): string => $key.' : '.(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)),
            array_keys($properties),
            array_values($properties),
        ));
    }
};
