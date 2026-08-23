<?php

use App\Enums\AdminPermission;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\AdminSupportSession;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Sessions support')] class extends Component
{
    use WithPagination;

    private const DEFAULT_DURATION_MINUTES = 30;

    private const DURATION_OPTIONS = [
        15,
        30,
        60,
        120,
    ];

    public string $search = '';

    public int $perPage = 10;

    /** @var array{status: string} */
    public array $filters = [
        'status' => 'all',
    ];

    public string $sessionTenantId = '';

    public string $sessionReason = '';

    public int $sessionDurationMinutes = self::DEFAULT_DURATION_MINUTES;

    public ?int $snapshotSessionId = null;

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SupportSession->value, 'admin'), 403);
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
    public function sessions(): LengthAwarePaginator
    {
        return AdminSupportSession::query()
            ->with(['adminUser', 'tenant'])
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('reason', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('adminUser', fn ($query) => $query->where('email', 'ilike', '%'.$this->search.'%'))
                    ->orWhereHas('tenant', fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'));
            }))
            ->when($this->filters['status'] === 'active', fn ($query) => $query->whereNull('ended_at')->where('expires_at', '>', now()))
            ->when($this->filters['status'] === 'expired', fn ($query) => $query->whereNull('ended_at')->where('expires_at', '<=', now()))
            ->when($this->filters['status'] === 'ended', fn ($query) => $query->whereNotNull('ended_at'))
            ->orderByDesc('started_at')
            ->paginate($this->perPage);
    }

    /**
     * Boutiques éligibles à une session support (statut actif ou essai).
     *
     * @return Collection<int, array{id: string, label: string}>
     */
    #[Computed]
    public function tenantOptions(): Collection
    {
        return Tenant::query()
            ->whereIn('status', [TenantStatus::Active, TenantStatus::Trialing])
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'label' => $tenant->name.' ('.$tenant->domains->first()?->domain.')',
            ]);
    }

    /**
     * Ouverture d'une session support (CdC § 12) : motif obligatoire, durée
     * limitée — journalisée dans `admin_audit_logs`.
     */
    public function startSession(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SupportSession->value, 'admin'), 403);

        $validated = $this->validate([
            'sessionTenantId' => ['required', 'exists:tenants,id'],
            'sessionReason' => ['required', 'string', 'min:10', 'max:500'],
            'sessionDurationMinutes' => ['required', 'integer', 'in:'.implode(',', self::DURATION_OPTIONS)],
        ]);

        $session = AdminSupportSession::create([
            'admin_user_id' => auth('admin')->id(),
            'tenant_id' => $validated['sessionTenantId'],
            'reason' => $validated['sessionReason'],
            'duration_minutes' => $validated['sessionDurationMinutes'],
            'expires_at' => now()->addMinutes($validated['sessionDurationMinutes']),
        ]);

        AdminAuditLog::record(
            'support.session_started',
            ['tenant' => $session->tenant->name, 'duration_minutes' => $session->duration_minutes, 'reason' => Str::limit($session->reason, 60)],
            $session->tenant_id,
        );

        $this->reset(['sessionTenantId', 'sessionReason', 'sessionDurationMinutes']);
        $this->sessionDurationMinutes = self::DEFAULT_DURATION_MINUTES;

        Flux::modal('start-support-session')->close();
        Flux::toast(__('Session support ouverte.'));
    }

    /**
     * Clôture manuelle d'une session active — journalisée.
     */
    public function endSession(int $sessionId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SupportSession->value, 'admin'), 403);

        $session = AdminSupportSession::with('tenant')->findOrFail($sessionId);

        abort_unless($session->isActive(), 404);

        $session->update(['ended_at' => now()]);

        AdminAuditLog::record(
            'support.session_ended',
            ['tenant' => $session->tenant->name, 'duration_minutes' => $session->duration_minutes],
            $session->tenant_id,
        );

        Flux::toast(__('Session support terminée.'));
    }

    public function showSnapshot(int $sessionId): void
    {
        $session = AdminSupportSession::with('tenant')->findOrFail($sessionId);

        abort_unless($session->isActive(), 404);

        $this->snapshotSessionId = $sessionId;

        Flux::modal('support-session-snapshot')->show();
    }

    /**
     * Métriques de la boutique en lecture seule (CdC § 12) : transaction
     * `READ ONLY` — toute écriture échouerait au niveau SQL.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function snapshot(): array
    {
        $session = AdminSupportSession::with('tenant')->findOrFail($this->snapshotSessionId);

        abort_unless($session->isActive(), 404);

        return $session->tenant->run(function (): array {
            return DB::transaction(function (): array {
                DB::statement('SET TRANSACTION READ ONLY');

                return [
                    'users_total' => DB::table('users')->count(),
                    'users_active' => DB::table('users')->where('is_active', true)->count(),
                    'invitations_pending' => DB::table('tenant_user_invitations')->whereNull('accepted_at')->count(),
                    'jobs_pending' => DB::table('jobs')->count(),
                    'jobs_failed' => DB::table('failed_jobs')->count(),
                ];
            });
        });
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'expired' => 'Expirée',
            'ended' => 'Terminée',
            default => $status,
        };
    }

    /**
     * @return list<int>
     */
    public function durationOptions(): array
    {
        return self::DURATION_OPTIONS;
    }
};
