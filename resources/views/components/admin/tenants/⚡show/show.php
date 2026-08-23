<?php

use App\Enums\AdminPermission;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\ManagerInvitationMail;
use App\Models\AdminAuditLog;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use App\Models\User;
use Database\Seeders\TenantRolesSeeder;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new #[Layout('layouts.admin')] #[Title('Boutique')] class extends Component
{
    private const TRIAL_EXTENSION_DAYS = 14;

    public string $tenantId = '';

    public string $inviteManagerName = '';

    public string $inviteManagerEmail = '';

    public string $newDomain = '';

    #[Computed]
    public function tenant(): Tenant
    {
        return Tenant::with('domains')->findOrFail($this->tenantId);
    }

    #[Computed]
    public function canManage(): bool
    {
        return Auth::guard('admin')->user()?->hasPermissionTo(AdminPermission::TenantManage->value, 'admin') ?? false;
    }

    #[Computed]
    public function canToggleMaintenance(): bool
    {
        return Auth::guard('admin')->user()?->hasPermissionTo(AdminPermission::MaintenanceToggle->value, 'admin') ?? false;
    }

    #[Computed]
    public function hasValidManager(): bool
    {
        return $this->tenant->hasValidManager();
    }

    #[Computed]
    public function invitations(): Collection
    {
        return $this->tenant->run(function (): Collection {
            return TenantUserInvitation::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (TenantUserInvitation $invitation): array => [
                    'id' => $invitation->id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'created_at' => $invitation->created_at?->format('d/m/Y H:i'),
                    'expires_at' => $invitation->expires_at->format('d/m/Y H:i'),
                    'accepted_at' => $invitation->accepted_at?->format('d/m/Y H:i'),
                    'is_pending' => $invitation->accepted_at === null,
                    'is_valid' => $invitation->isValid(),
                ]);
        });
    }

    public function mount(string $tenant): void
    {
        $this->tenantId = $tenant;
    }

    /**
     * Suspension / réactivation (CdC § 5.1, plan-sprint0 § 5.7) — chaque
     * mutation est journalisée dans `admin_audit_logs`. Une boutique ne peut
     * être activée qu'avec un gérant actif (CdC § 2.5).
     */
    public function toggleStatus(): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        if ($tenant->status !== TenantStatus::Active && ! $tenant->hasValidManager()) {
            abort(422, __('Une boutique ne peut être activée qu\'avec un gérant actif.'));
        }

        $tenant->update([
            'status' => $tenant->status === TenantStatus::Active
                ? TenantStatus::Suspended
                : TenantStatus::Active,
        ]);

        AdminAuditLog::record(
            'tenant.status_changed',
            ['status' => $tenant->fresh()->status->value],
            $tenant->id,
        );
    }

    /**
     * Renvoi de l'invitation gérant (plan-sprint0 § 5.5) : nouveau token,
     * nouvelle expiration (48 h) et e-mail au gérant — de l'invitation ciblée
     * si un identifiant est fourni, sinon de la dernière invitation en attente.
     * Les invitations déjà consommées ne sont jamais renvoyées. Réservé aux
     * boutiques en attente (aucun nouveau gérant une fois la boutique active).
     */
    public function resendInvitation(?int $invitationId = null): void
    {
        abort_unless($this->canManage, 403);

        abort_unless($this->tenant->status === TenantStatus::Pending, 422, __('Impossible de renvoyer une invitation : la boutique est déjà activée.'));

        $tenant = $this->tenant;
        $domain = $tenant->domains->first()?->domain;

        $sent = false;

        $tenant->run(function () use ($tenant, $domain, $invitationId, &$sent): void {
            $invitation = $invitationId !== null
                ? TenantUserInvitation::find($invitationId)
                : TenantUserInvitation::whereNull('accepted_at')->latest()->first();

            if (! $invitation || $invitation->accepted_at !== null) {
                return;
            }

            $invitation->update([
                'token' => Str::random(64),
                'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', TenantUserInvitation::EXPIRES_IN_HOURS)),
            ]);

            Mail::to($invitation->email)->send(new ManagerInvitationMail(
                managerName: $invitation->name,
                shopName: $tenant->name,
                acceptUrl: Tenant::url(Str::before($domain, '.'), 'tenant-invitations/'.$invitation->token),
                expiresAt: $invitation->expires_at,
            ));

            $sent = true;
        });

        if (! $sent) {
            throw ValidationException::withMessages([
                'invitation' => __('Aucune invitation en attente pour cette boutique.'),
            ]);
        }

        AdminAuditLog::record('tenant.invitation_resent', ['domain' => $domain], $tenant->id);

        Flux::toast(__('Invitation renvoyée au gérant.'));
    }

    /**
     * Révocation d'une invitation gérant : l'invitation et le compte inactif
     * associé sont supprimés. Seule une invitation en attente peut être
     * révoquée.
     */
    public function revokeInvitation(int $invitationId): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;
        $domain = $tenant->domains->first()?->domain;
        $email = null;

        $tenant->run(function () use ($invitationId, &$email): void {
            $invitation = TenantUserInvitation::whereNull('accepted_at')->find($invitationId);

            abort_unless($invitation, 404);

            $email = $invitation->email;

            User::where('email', $invitation->email)->where('is_active', false)->delete();

            $invitation->delete();
        });

        AdminAuditLog::record('tenant.invitation_revoked', ['email' => $email, 'domain' => $domain], $tenant->id);

        Flux::toast(__('Invitation révoquée.'));
    }

    /**
     * Invitation d'un gérant pour une boutique qui n'en a pas (plan-sprint0
     * § 5.5) : les invitations en attente sont remplacées par la nouvelle.
     * Réservé aux boutiques en attente — un gérant supplémentaire ne peut
     * être ajouté une fois la boutique activée.
     */
    public function inviteManager(): void
    {
        abort_unless($this->canManage, 403);

        abort_unless($this->tenant->status === TenantStatus::Pending, 422, __('Impossible d\'inviter un gérant : la boutique est déjà activée.'));

        $this->validate([
            'inviteManagerName' => ['required', 'string', 'max:255'],
            'inviteManagerEmail' => ['required', 'email', 'max:255'],
        ]);

        if (Tenant::managerEmailExists($this->inviteManagerEmail)) {
            throw ValidationException::withMessages([
                'inviteManagerEmail' => __('Cet e-mail est déjà utilisé.'),
            ]);
        }

        $tenant = $this->tenant;
        $domain = $tenant->domains->first()?->domain;

        $userLimit = $tenant->planLimits()['users'] ?? null;

        if ($userLimit !== null && $tenant->run(fn (): int => User::count()) >= $userLimit) {
            throw ValidationException::withMessages([
                'inviteManagerEmail' => __('La limite d\'utilisateurs du plan est atteinte.'),
            ]);
        }

        $tenant->run(function () use ($tenant, $domain): void {
            TenantUserInvitation::whereNull('accepted_at')->delete();

            $invitation = TenantUserInvitation::create([
                'name' => $this->inviteManagerName,
                'email' => $this->inviteManagerEmail,
                'token' => Str::random(64),
                'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', TenantUserInvitation::EXPIRES_IN_HOURS)),
            ]);

            Mail::to($this->inviteManagerEmail)->send(new ManagerInvitationMail(
                managerName: $this->inviteManagerName,
                shopName: $tenant->name,
                acceptUrl: Tenant::url(Str::before($domain, '.'), 'tenant-invitations/'.$invitation->token),
                expiresAt: $invitation->expires_at,
            ));
        });

        AdminAuditLog::record('tenant.invitation_sent', ['email' => $this->inviteManagerEmail], $tenant->id);

        $this->reset(['inviteManagerName', 'inviteManagerEmail']);

        Flux::toast(__('Invitation envoyée au gérant.'));

        Flux::modal('invite-manager')->close();
    }

    /**
     * Remplacement du gérant d'une boutique active (transfert de propriété) :
     * une invitation de remplacement est envoyée ; le gérant actuel reste
     * actif jusqu'à l'acceptation, moment où l'accept flow désactive l'ancien.
     * Réservé aux boutiques ayant déjà un gérant — jamais en attente
     * d'activation.
     */
    public function replaceManager(): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        abort_unless($tenant->status !== TenantStatus::Pending
            && $tenant->status !== TenantStatus::Cancelled
            && $tenant->hasValidManager(), 422, __('Aucun gérant à remplacer pour cette boutique.'));

        $this->validate([
            'inviteManagerName' => ['required', 'string', 'max:255'],
            'inviteManagerEmail' => ['required', 'email', 'max:255'],
        ]);

        if (Tenant::managerEmailExists($this->inviteManagerEmail)) {
            throw ValidationException::withMessages([
                'inviteManagerEmail' => __('Cet e-mail est déjà utilisé.'),
            ]);
        }

        $tenant = $this->tenant;
        $domain = $tenant->domains->first()?->domain;

        $previousManagerEmail = $tenant->run(fn (): ?string => User::role(TenantRolesSeeder::ROLE_MANAGER)->where('is_active', true)->value('email'));

        $tenant->run(function () use ($tenant, $domain): void {
            TenantUserInvitation::whereNull('accepted_at')->delete();

            $invitation = TenantUserInvitation::create([
                'name' => $this->inviteManagerName,
                'email' => $this->inviteManagerEmail,
                'token' => Str::random(64),
                'expires_at' => now()->addHours((int) Setting::get('security.invitation_expires_hours', TenantUserInvitation::EXPIRES_IN_HOURS)),
            ]);

            Mail::to($this->inviteManagerEmail)->send(new ManagerInvitationMail(
                managerName: $this->inviteManagerName,
                shopName: $tenant->name,
                acceptUrl: Tenant::url(Str::before($domain, '.'), 'tenant-invitations/'.$invitation->token),
                expiresAt: $invitation->expires_at,
            ));
        });

        AdminAuditLog::record('tenant.manager_replaced', [
            'previous_manager_email' => $previousManagerEmail,
            'new_manager_email' => $this->inviteManagerEmail,
        ], $tenant->id);

        $this->reset(['inviteManagerName', 'inviteManagerEmail']);

        Flux::toast(__('Invitation de remplacement envoyée au nouveau gérant.'));

        Flux::modal('replace-manager')->close();
    }

    /**
     * Prolongation d'essai (CdC § 12) : +14 jours à partir de la fin d'essai
     * si elle est encore future, sinon à partir d'aujourd'hui — réservée aux
     * abonnements en essai, journalisée dans `admin_audit_logs`.
     */
    public function extendTrial(): void
    {
        abort_unless($this->canManage, 403);

        $subscription = $this->tenant->subscription;

        abort_unless($subscription?->status === SubscriptionStatus::Trialing, 404);

        $base = $subscription->trial_ends_at?->isFuture() ? $subscription->trial_ends_at : now();

        $subscription->update([
            'trial_ends_at' => $base->addDays(self::TRIAL_EXTENSION_DAYS),
        ]);

        AdminAuditLog::record(
            'tenant.trial_extended',
            ['days' => self::TRIAL_EXTENSION_DAYS, 'trial_ends_at' => $subscription->fresh()->trial_ends_at->toDateString()],
            $this->tenant->id,
        );

        Flux::toast(__('Essai prolongé de :days jours.', ['days' => self::TRIAL_EXTENSION_DAYS]));
    }

    /**
     * Annulation / archivage d'une boutique (CdC § 12) : statut `cancelled`
     * (accès web bloqués) + abonnement annulé ; la suppression définitive
     * n'est possible qu'après cet archivage.
     */
    public function cancelTenant(): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        abort_unless($tenant->status !== TenantStatus::Cancelled, 404);

        $tenant->update(['status' => TenantStatus::Cancelled]);

        if ($subscription = $tenant->subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
        }

        AdminAuditLog::record(
            'tenant.cancelled',
            ['name' => $tenant->name, 'domain' => $tenant->domains->first()?->domain],
            $tenant->id,
        );

        Flux::toast(__('Boutique annulée et archivée.'));
    }

    /**
     * Suppression définitive d'un tenant (plan-sprint0 § 5.5, CdC § 12) :
     * uniquement après archivage (`cancelled`), audit avant suppression,
     * puis pipeline `TenantDeleted` (domaines en cascade, schéma supprimé).
     */
    public function deleteTenant(): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        abort_unless($tenant->status === TenantStatus::Cancelled, 404);

        AdminAuditLog::record(
            'tenant.deleted',
            ['name' => $tenant->name, 'domain' => $tenant->domains->first()?->domain],
            $tenant->id,
        );

        $tenant->delete();

        Flux::toast(__('Boutique supprimée.'));

        $this->redirect(route('admin.tenants.index'));
    }

    /**
     * Fenêtre de maintenance (CdC § 12, opérations) : bascule 2 heures à
     * partir de maintenant, ou levée immédiate — permission dédiée
     * `admin.maintenance.toggle` (Super-Admin uniquement).
     */
    public function toggleMaintenance(): void
    {
        abort_unless($this->canToggleMaintenance, 403);

        $tenant = $this->tenant;

        $tenant->update([
            'maintenance_until' => $tenant->isUnderMaintenance() ? null : now()->addHours(2),
        ]);

        AdminAuditLog::record(
            'tenant.maintenance_toggled',
            [
                'enabled' => $tenant->fresh()->isUnderMaintenance(),
                'until' => $tenant->fresh()->maintenance_until?->toIso8601String(),
            ],
            $tenant->id,
        );

        Flux::toast(
            $tenant->fresh()->isUnderMaintenance()
                ? __('Maintenance activée pour 2 heures.')
                : __('Maintenance levée.'),
        );
    }

    /**
     * Purge du cache de la boutique (CdC § 12, opérations) : le cache est
     * isolé par tenant (schéma dédié), le vidage ne touche que le sien.
     */
    public function purgeCache(): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        $tenant->run(fn (): bool => cache()->flush());

        AdminAuditLog::record('tenant.cache_purged', [], $tenant->id);

        Flux::toast(__('Cache de la boutique purgé.'));
    }

    /**
     * Export des données d'une boutique (portabilité RGPD, CdC § 12) :
     * JSON téléchargé contenant l'identité, les utilisateurs et les
     * invitations — journalisé dans `admin_audit_logs`.
     */
    public function exportData(): BinaryFileResponse
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        $payload = $tenant->run(function () use ($tenant): array {
            return [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'status' => $tenant->status->value,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                ],
                'users' => User::query()->get(['id', 'name', 'email', 'is_active', 'created_at'])->toArray(),
                'invitations' => TenantUserInvitation::query()->get(['id', 'name', 'email', 'accepted_at', 'expires_at'])->toArray(),
                'exported_at' => now()->toIso8601String(),
            ];
        });

        AdminAuditLog::record('tenant.data_exported', ['domain' => $tenant->domains->first()?->domain], $tenant->id);

        $path = storage_path('app/exports/export-'.$tenant->id.'-'.now()->format('Ymd-His').'.json');

        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }

    /**
     * Ajout d'un sous-domaine (CdC § 12, domaines) : validation du label,
     * unicité globale dans `domains`, journalisation.
     */
    public function addDomain(): void
    {
        abort_unless($this->canManage, 403);

        $this->validate([
            'newDomain' => ['required', 'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/'],
        ]);

        $domain = $this->newDomain.'.'.config('tenancy.root_domain');

        if (Tenant::whereHas('domains', fn ($query) => $query->where('domain', $domain))->exists()) {
            throw ValidationException::withMessages([
                'newDomain' => __('Ce sous-domaine est déjà utilisé.'),
            ]);
        }

        $tenant = $this->tenant;

        $tenant->domains()->create(['domain' => $domain]);

        AdminAuditLog::record('tenant.domain_added', ['domain' => $domain], $tenant->id);

        $this->reset('newDomain');

        Flux::toast(__('Domaine ajouté.'));
    }

    /**
     * Retrait d'un sous-domaine — le dernier domaine d'une boutique n'est
     * jamais supprimé (il porte son accès web).
     */
    public function removeDomain(string $domain): void
    {
        abort_unless($this->canManage, 403);

        $tenant = $this->tenant;

        abort_unless($tenant->domains->count() > 1, 422);

        $tenant->domains()->where('domain', $domain)->delete();

        AdminAuditLog::record('tenant.domain_removed', ['domain' => $domain], $tenant->id);

        Flux::toast(__('Domaine retiré.'));
    }

    public function impersonate(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::TenantView->value, 'admin'), 403);

        $tenant = $this->tenant;

        $manager = $tenant->run(function (): ?User {
            return User::query()->first();
        });

        abort_unless($manager !== null, 404, 'Aucun utilisateur dans cette boutique.');

        $token = Str::random(64);

        Cache::put('impersonate:'.$token, [
            'tenant_id' => $tenant->id,
            'user_id' => $manager->id,
            'admin_id' => auth('admin')->id(),
        ], 300);

        AdminAuditLog::record('tenant.impersonated', ['email' => $manager->email], $tenant->id);

        $domain = $tenant->domains->first()?->domain ?? $tenant->id.'.'.config('tenancy.root_domain');

        $this->redirect('http://'.$domain.'/impersonate/'.$token, navigate: true);
    }
};
