<?php

use App\Enums\AdminPermission;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts.admin')] #[Title('Boutiques')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var array{status: string} */
    public array $filters = [
        'status' => 'all',
    ];

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

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tenants(): LengthAwarePaginator
    {
        return Tenant::query()
            ->with('domains')
            ->when($this->search, fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::TenantView->value, 'admin'), 403);

        $tenants = Tenant::query()
            ->when($this->search, fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->with('domains')
            ->orderByDesc('created_at')
            ->get();

        AdminAuditLog::record('tenant.exported', ['count' => $tenants->count()]);

        $filename = 'boutiques-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($tenants): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nom', 'Statut', 'Domaines', 'Créée le'], separator: ';');
            foreach ($tenants as $tenant) {
                fputcsv($handle, [
                    $tenant->name,
                    $tenant->status->value,
                    $tenant->domains->pluck('domain')->join(', '),
                    $tenant->created_at?->format('d/m/Y H:i'),
                ], separator: ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    #[On('tenant-created')]
    public function refresh(): void
    {
        $this->resetPage();
    }
};
