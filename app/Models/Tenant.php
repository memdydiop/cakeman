<?php

namespace App\Models;

use App\Concerns\HasPlanFeatures;
use App\Enums\TenantStatus;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Tenant (schéma central `public`, CdC § 4.2) — le plan est porté par
 * `subscriptions` (Module 12, sprint 9), pas par le tenant.
 *
 * @property string $id
 * @property string $name
 * @property TenantStatus $status
 * @property Carbon|null $maintenance_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasPlanFeatures;

    protected $fillable = [
        'name',
        'status',
        'maintenance_until',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'maintenance_until' => 'datetime',
        ];
    }

    /**
     * Règle CdC § 5.1 : un tenant suspendu n'accède ni au web ni aux webhooks.
     */
    public function isAccessible(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    /**
     * Fenêtre de maintenance (CdC § 12, opérations) — la boutique est bloquée
     * (503) tant que `maintenance_until` est dans le futur.
     */
    public function isUnderMaintenance(): bool
    {
        return $this->maintenance_until !== null && $this->maintenance_until->isFuture();
    }

    /**
     * Un gérant valide est un utilisateur actif portant le rôle Gérant dans le
     * schéma de la boutique (CdC § 2.5) : une boutique ne peut être activée
     * qu'avec un tel compte.
     */
    public function hasValidManager(): bool
    {
        return $this->run(fn (): bool => User::role(TenantRolesSeeder::ROLE_MANAGER)->where('is_active', true)->exists());
    }

    /**
     * Abonnement de la boutique (Module 12, schéma central).
     */
    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Limites du plan de la boutique (Module 12) — plan Gratuit par défaut
     * en l'absence d'abonnement.
     *
     * @return array{users: int|null, orders_per_month: int|null, storage_gb: int|null}
     */
    public function planLimits(): array
    {
        $defaults = ['users' => 1, 'orders_per_month' => 15, 'storage_gb' => 1];

        $plan = $this->subscription()->first()?->plan()->first();

        if ($plan !== null) {
            return $plan->limits;
        }

        $freePlan = Plan::where('code', 'free')->first();

        return $freePlan !== null ? $freePlan->limits : $defaults;
    }

    /**
     * Initiales affichées dans l'avatar du dashboard.
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * URL d'une boutique : `http://{sous-domaine}.{root_domain}[:dev_port]{path}`.
     */
    public static function url(string $subdomain, string $path = ''): string
    {
        $port = config('tenancy.dev_port');

        return 'http://'.$subdomain.'.'.config('tenancy.root_domain').($port ? ':'.$port : '').'/'.ltrim($path, '/');
    }

    /**
     * L'e-mail d'un gérant doit être unique à l'échelle de la plateforme
     * (CdC § 2.5) : vérifie les comptes (tenants actifs) et les invitations
     * en attente (tenants en attente).
     */
    public static function managerEmailExists(string $email): bool
    {
        foreach (Tenant::whereIn('status', [TenantStatus::Active, TenantStatus::Pending])->pluck('id') as $id) {
            $tenant = Tenant::query()->whereKey($id)->first();

            if ($tenant !== null && $tenant->run(function () use ($email): bool {
                return User::where('email', $email)->exists()
                    || TenantUserInvitation::where('email', $email)->whereNull('accepted_at')->exists();
            })) {
                return true;
            }
        }

        return false;
    }

    /**
     * Colonnes réelles de la table `tenants` : `name` et `status` vivent hors
     * de la colonne JSON `data` (VirtualColumn de stancl) afin d'être
     * interrogeables en SQL (recherche console, tests).
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'status'];
    }
}
