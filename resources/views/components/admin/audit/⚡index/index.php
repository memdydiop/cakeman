<?php

use App\Enums\AdminPermission;
use App\Models\AdminAuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Audit')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var array{action: string} */
    public array $filters = [
        'action' => 'all',
    ];

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::AuditView->value, 'admin'), 403);
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return AdminAuditLog::query()
            ->with(['adminUser', 'tenant'])
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('action', 'ilike', '%'.$this->search.'%')
                    ->orWhere('ip_address', 'ilike', '%'.$this->search.'%')
                    ->orWhereRaw('properties::text ilike ?', ['%'.$this->search.'%'])
                    ->orWhereHas('adminUser', fn ($query) => $query->where('email', 'ilike', '%'.$this->search.'%'))
                    ->orWhereHas('tenant', fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'));
            }))
            ->when($this->filters['action'] !== 'all', fn ($query) => $query->where('action', $this->filters['action']))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function actions(): array
    {
        return AdminAuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    public function actionLabel(string $action): string
    {
        return match ($action) {
            'tenant.created' => 'Boutique créée',
            'tenant.status_changed' => 'Statut de boutique modifié',
            'tenant.deleted' => 'Boutique supprimée',
            'tenant.trial_extended' => 'Essai prolongé',
            'tenant.cancelled' => 'Boutique annulée (archivée)',
            'tenant.maintenance_toggled' => 'Maintenance basculée',
            'tenant.cache_purged' => 'Cache purgé',
            'tenant.data_exported' => 'Données exportées',
            'tenant.domain_added' => 'Domaine ajouté',
            'tenant.domain_removed' => 'Domaine retiré',
            'tenant.archived' => 'Boutique archivée (suspension > 30 j)',
            'lead.contacted' => 'Lead contacté',
            'lead.converted' => 'Lead converti',
            'settings.updated' => 'Configuration modifiée',
            'tenant.invitation_sent' => 'Invitation gérant envoyée',
            'tenant.invitation_resent' => 'Invitation gérant renvoyée',
            'tenant.invitation_revoked' => 'Invitation gérant révoquée',
            'support.session_started' => 'Session support ouverte',
            'support.session_ended' => 'Session support terminée',
            'invoice.created' => 'Facture générée',
            'invoice.paid' => 'Facture payée',
            'admin_user.invited' => 'Utilisateur invité',
            'admin_user.updated' => 'Utilisateur modifié',
            'admin_user.deleted' => 'Utilisateur supprimé',
            'admin_user.invitation_resent' => 'Invitation renvoyée',
            'admin_user.invitation_revoked' => 'Invitation révoquée',
            'admin_user.password_reset_sent' => 'Lien de réinitialisation envoyé',
            'admin_user.password_changed' => 'Mot de passe modifié',
            'admin_user.gdpr_exported' => 'Export RGPD',
            'admin_user.exported' => 'Export des utilisateurs',
            'plan.created' => 'Plan créé',
            'plan.updated' => 'Plan modifié',
            'plan.toggled' => 'Plan activé / désactivé',
            'addon.created' => 'Add-on créé',
            'addon.updated' => 'Add-on modifié',
            'addon.toggled' => 'Add-on activé / désactivé',
            'subscription.addons_updated' => 'Add-ons d\'abonnement modifiés',
            'subscription.cancelled' => 'Abonnement annulé',
            'subscription.trial_ended' => 'Fin d\'essai',
            'subscription.past_due' => 'Abonnement en retard',
            'subscription.suspended' => 'Boutique suspendue (impayé)',
            'tenant.exported' => 'Boutiques exportées',
            'tenant.impersonated' => 'Impersonation',
            'invoice.reminded' => 'Relance facture',
            'credit_note.created' => 'Avoir créé',
            'coupon.created' => 'Coupon créé',
            'coupon.updated' => 'Coupon modifié',
            'coupon.toggled' => 'Coupon activé / désactivé',
            'announcement.published' => 'Annonce publiée',
            'announcement.deleted' => 'Annonce supprimée',
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
