<?php

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\AdminWebhookLog;
use App\Models\Lead;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Dashboard')] class extends Component
{
    public int $totalTenants = 0;

    /** @var array<string, int> */
    public array $tenantsByStatus = [];

    public int $totalAdminUsers = 0;

    public int $tenantTrend = 0;

    /** @var list<array{label: string, count: int}> */
    public array $creationsByMonth = [];

    public function mount(): void
    {
        $cached = Cache::remember('dashboard.mount', 60, function (): array {
            $monthly = Tenant::query()
                ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total")
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->pluck('total', 'month');

            $creations = collect(range(11, 0))
                ->map(function (int $offset) use ($monthly): array {
                    $month = now()->subMonths($offset)->startOfMonth();

                    return [
                        'label' => $month->translatedFormat('M'),
                        'count' => (int) ($monthly[$month->format('Y-m')] ?? 0),
                    ];
                })
                ->all();

            return [
                'totalTenants' => Tenant::count(),
                'tenantsByStatus' => Tenant::query()
                    ->select('status')
                    ->selectRaw('count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->map(fn (int $total): int => $total)
                    ->all(),
                'totalAdminUsers' => AdminUser::count(),
                'creationsByMonth' => $creations,
            ];
        });

        $this->totalTenants = $cached['totalTenants'];
        $this->tenantsByStatus = $cached['tenantsByStatus'];
        $this->totalAdminUsers = $cached['totalAdminUsers'];
        $this->creationsByMonth = $cached['creationsByMonth'];

        $this->tenantTrend = $this->growthRate(
            $this->creationsByMonth[count($this->creationsByMonth) - 2]['count'],
            $this->creationsByMonth[count($this->creationsByMonth) - 1]['count'],
        );
    }

    public function statusLabel(TenantStatus $status): string
    {
        return match ($status) {
            TenantStatus::Active => 'Active',
            TenantStatus::Pending => 'En attente',
            TenantStatus::Suspended => 'Suspendue',
            TenantStatus::Trialing => 'Essai',
            TenantStatus::Cancelled => 'Annulée',
        };
    }

    /**
     * Revenu mensuel récurrent (Module 12) : abonnements actifs et en retard,
     * annualisé pour le mensuel.
     */
    #[Computed]
    public function mrr(): int
    {
        return (int) Cache::remember('dashboard.mrr', 60, function (): int {
            return (int) Subscription::query()
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->with('plan')
                ->get()
                ->sum(fn (Subscription $subscription): int => $subscription->billing_period === BillingPeriod::Annual
                    ? (int) round($subscription->plan->annual_price / 12)
                    : $subscription->plan->monthly_price);
        });
    }

    #[Computed]
    public function pendingJobs(): int
    {
        return (int) DB::table('jobs')->count();
    }

    #[Computed]
    public function failedJobs(): int
    {
        return (int) DB::table('failed_jobs')->count();
    }

    /** @return array{processed: int, failed: int} */
    #[Computed]
    public function webhooksLast24h(): array
    {
        return Cache::remember('dashboard.webhooks24h', 60, function (): array {
            $counts = AdminWebhookLog::query()
                ->where('created_at', '>=', now()->subHours(24))
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'processed' => (int) ($counts['processed'] ?? 0) + (int) ($counts['ignored'] ?? 0),
                'failed' => (int) ($counts['failed'] ?? 0) + (int) ($counts['mismatch'] ?? 0),
            ];
        });
    }

    /**
     * @return Collection<int, Tenant>
     */
    #[Computed]
    public function recentTenants(): Collection
    {
        return Tenant::query()
            ->with('domains')
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, AdminUser>
     */
    #[Computed]
    public function recentAdminUsers(): Collection
    {
        return AdminUser::query()
            ->with('roles')
            ->latest()
            ->limit(5)
            ->get();
    }

    /** @return array{total: int, converted: int} */
    #[Computed]
    public function leadStats(): array
    {
        return Cache::remember('dashboard.leadStats', 60, function (): array {
            $counts = Lead::query()
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total' => (int) array_sum($counts->all()),
                'converted' => (int) ($counts['converted'] ?? 0),
            ];
        });
    }

    /** @return list<int> MRR estimé sur les 6 derniers mois (FCFA/mois). */
    #[Computed]
    public function monthlyRevenue(): array
    {
        return Cache::remember('dashboard.monthlyRevenue', 60, function (): array {
            $active = Subscription::query()
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->with('plan')
                ->get();

            return collect(range(5, 0))
                ->map(function (int $offset) use ($active): int {
                    $monthEnd = now()->subMonths($offset)->endOfMonth();

                    return (int) $active->filter(function (Subscription $subscription) use ($monthEnd): bool {
                        if ($subscription->created_at->gt($monthEnd)) {
                            return false;
                        }

                        return $subscription->cancelled_at === null || $subscription->cancelled_at->gt($monthEnd);
                    })->sum(fn (Subscription $subscription): int => $subscription->billing_period === BillingPeriod::Annual
                        ? (int) round($subscription->plan->annual_price / 12)
                        : $subscription->plan->monthly_price);
                })
                ->all();
        });
    }

    /** @return list<int> Leads créés par mois sur les 6 derniers mois. */
    #[Computed]
    public function leadsByMonth(): array
    {
        return Cache::remember('dashboard.leadsByMonth', 60, function (): array {
            $monthly = Lead::query()
                ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total")
                ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('month')
                ->pluck('total', 'month');

            return collect(range(5, 0))
                ->map(fn (int $offset): int => (int) ($monthly[now()->subMonths($offset)->startOfMonth()->format('Y-m')] ?? 0))
                ->all();
        });
    }

    /** @return Collection<int, object> */
    #[Computed]
    /** @return Collection<int, array{tenant: Tenant, mrr: int, domain: ?string, initials: string}> */
    public function topTenantsByMrr(): Collection
    {
        // v2 : cache sérialisable (pas d'objet stdClass avec modèle) — évite __PHP_Incomplete_Class
        $cached = Cache::remember('dashboard.topTenantsByMrr.v2', 60, function (): array {
            $subs = Subscription::query()
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->with(['plan', 'tenant.domains'])
                ->get()
                ->groupBy('tenant_id');

            return $subs->map(function (Collection $group): ?array {
                $tenant = $group->first()->tenant;
                if (! $tenant) {
                    return null;
                }

                $mrr = $group->sum(fn (Subscription $s): int => $s->billing_period === BillingPeriod::Annual
                    ? (int) round($s->plan->annual_price / 12)
                    : $s->plan->monthly_price);

                return [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'initials' => $tenant->initials(),
                    'domain' => $tenant->domains->first()?->domain,
                    'mrr' => $mrr,
                ];
            })
                ->filter()
                ->sortByDesc(fn (array $row): int => $row['mrr'])
                ->take(5)
                ->values()
                ->all();
        });

        $ids = collect($cached)->pluck('tenant_id')->all();

        $tenants = Tenant::whereIn('id', $ids)->with('domains')->get()->keyBy('id');

        return collect($cached)->map(fn (array $row): array => [
            'tenant' => $tenants->get($row['tenant_id']),
            'tenant_name' => $row['tenant_name'],
            'initials' => $row['initials'],
            'domain' => $row['domain'],
            'mrr' => $row['mrr'],
        ])->filter(fn (array $row): bool => $row['tenant'] !== null)->values();
    }

    /**
     * @return Collection<int, AdminAuditLog>
     */
    #[Computed]
    public function recentActivity(): Collection
    {
        return AdminAuditLog::query()
            ->with(['adminUser', 'tenant'])
            ->latest()
            ->limit(8)
            ->get();
    }

    /** @return array{icon: string, color: string} */
    public function activityStyle(string $action): array
    {
        return match (true) {
            str_starts_with($action, 'tenant.created') => ['icon' => 'building-storefront', 'color' => 'blue'],
            str_starts_with($action, 'tenant.archived'), str_starts_with($action, 'tenant.cancelled'), str_starts_with($action, 'tenant.deleted') => ['icon' => 'archive-box', 'color' => 'red'],
            str_starts_with($action, 'tenant.status_changed') => ['icon' => 'power', 'color' => 'amber'],
            str_starts_with($action, 'tenant.maintenance') => ['icon' => 'shield-exclamation', 'color' => 'amber'],
            str_starts_with($action, 'tenant.cache') => ['icon' => 'sparkles', 'color' => 'indigo'],
            str_starts_with($action, 'tenant.data_exported') => ['icon' => 'arrow-down-tray', 'color' => 'indigo'],
            str_starts_with($action, 'tenant.domain') => ['icon' => 'globe-alt', 'color' => 'blue'],
            str_starts_with($action, 'lead.') => ['icon' => 'megaphone', 'color' => 'green'],
            str_starts_with($action, 'invoice.') => ['icon' => 'receipt-percent', 'color' => 'green'],
            str_starts_with($action, 'subscription.') => ['icon' => 'credit-card', 'color' => 'blue'],
            str_starts_with($action, 'plan.') || str_starts_with($action, 'addon.') => ['icon' => 'tag', 'color' => 'indigo'],
            str_starts_with($action, 'admin_user.') => ['icon' => 'users', 'color' => 'zinc'],
            str_starts_with($action, 'settings.') => ['icon' => 'cog-6-tooth', 'color' => 'indigo'],
            str_starts_with($action, 'support.session') => ['icon' => 'user-circle', 'color' => 'amber'],
            str_starts_with($action, 'support.ticket') => ['icon' => 'lifebuoy', 'color' => 'amber'],
            str_starts_with($action, 'tenant.invitation') => ['icon' => 'paper-airplane', 'color' => 'blue'],
            default => ['icon' => 'clipboard-document-list', 'color' => 'zinc'],
        };
    }

    public function activityLabel(string $action): string
    {
        return match ($action) {
            'tenant.created' => 'Boutique créée',
            'tenant.status_changed' => 'Statut modifié',
            'tenant.deleted' => 'Boutique supprimée',
            'tenant.trial_extended' => 'Essai prolongé',
            'tenant.cancelled' => 'Boutique annulée',
            'tenant.archived' => 'Boutique archivée',
            'tenant.maintenance_toggled' => 'Maintenance basculée',
            'tenant.cache_purged' => 'Cache purgé',
            'tenant.data_exported' => 'Données exportées',
            'tenant.domain_added' => 'Domaine ajouté',
            'tenant.domain_removed' => 'Domaine retiré',
            'tenant.invitation_sent' => 'Invitation gérant envoyée',
            'tenant.invitation_resent' => 'Invitation renvoyée',
            'tenant.invitation_revoked' => 'Invitation révoquée',
            'lead.contacted' => 'Lead contacté',
            'lead.converted' => 'Lead converti',
            'invoice.created' => 'Facture générée',
            'invoice.paid' => 'Facture payée',
            'subscription.cancelled' => 'Abonnement annulé',
            'subscription.trial_ended' => 'Fin d\'essai',
            'subscription.past_due' => 'Abonnement en retard',
            'subscription.suspended' => 'Boutique suspendue',
            'plan.created' => 'Plan créé',
            'plan.updated' => 'Plan modifié',
            'addon.created' => 'Add-on créé',
            'addon.updated' => 'Add-on modifié',
            'admin_user.invited' => 'Opérateur invité',
            'admin_user.updated' => 'Opérateur modifié',
            'admin_user.deleted' => 'Opérateur supprimé',
            'settings.updated' => 'Configuration modifiée',
            'support.session_started' => 'Session support ouverte',
            'support.session_ended' => 'Session support terminée',
            'support.ticket_created' => 'Ticket créé',
            'support.ticket_assigned' => 'Ticket assigné',
            'support.ticket_replied' => 'Réponse ticket',
            'support.ticket_closed' => 'Ticket fermé',
            default => $action,
        };
    }

    private function growthRate(int $previous, int $current): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }
};
