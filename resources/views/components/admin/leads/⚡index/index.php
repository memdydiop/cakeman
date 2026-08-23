<?php

use App\Enums\AdminPermission;
use App\Enums\LeadStatus;
use App\Models\AdminAuditLog;
use App\Models\Lead;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new #[Layout('layouts.admin')] #[Title('Leads')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var array{status: string} */
    public array $filters = [
        'status' => 'all',
    ];

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::LeadView->value, 'admin'), 403);
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
    public function leads(): LengthAwarePaginator
    {
        return Lead::query()
            ->with('convertedTenant')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%')
                    ->orWhere('phone', 'ilike', '%'.$this->search.'%')
                    ->orWhere('shop_name', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    /** @return array{total: int, new: int, contacted: int, converted: int, conversion_rate: int} */
    #[Computed]
    public function stats(): array
    {
        $total = Lead::count();

        $counts = Lead::query()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => $total,
            'new' => (int) ($counts[LeadStatus::New->value] ?? 0),
            'contacted' => (int) ($counts[LeadStatus::Contacted->value] ?? 0),
            'converted' => (int) ($counts[LeadStatus::Converted->value] ?? 0),
            'conversion_rate' => $total > 0 ? (int) round(($counts[LeadStatus::Converted->value] ?? 0) / $total * 100) : 0,
        ];
    }

    public function statusLabel(LeadStatus $status): string
    {
        return $status->label();
    }

    public function statusColor(LeadStatus $status): string
    {
        return $status->color();
    }

    /** @return Collection<int, Tenant> */
    #[Computed]
    public function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get();
    }

    public ?int $pendingLeadId = null;

    public ?string $convertTenantId = null;

    /**
     * Conversion : ouvre la modale de choix de la boutique créée à partir
     * du lead (optionnel) avant de passer le statut à `converted`.
     */
    public function requestConversion(int $leadId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::LeadView->value, 'admin'), 403);

        $this->pendingLeadId = Lead::findOrFail($leadId)->id;

        $this->convertTenantId = null;

        Flux::modal('lead-convert')->show();
    }

    public function confirmConversion(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::LeadView->value, 'admin'), 403);

        $lead = Lead::findOrFail($this->pendingLeadId);

        $tenant = $this->convertTenantId !== null ? Tenant::findOrFail($this->convertTenantId) : null;

        $lead->update([
            'status' => LeadStatus::Converted,
            'converted_tenant_id' => $tenant?->id,
        ]);

        AdminAuditLog::record(
            'lead.converted',
            ['email' => $lead->email, 'tenant_id' => $tenant?->id],
            $tenant?->id,
        );

        Flux::modal('lead-convert')->close();

        $this->reset(['pendingLeadId', 'convertTenantId']);

        Flux::toast(__('Lead converti — boutique associée.'));
    }

    /**
     * Statut intermédiaire : `new → contacted` (pris en charge par l'équipe).
     */
    public function markContacted(int $leadId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::LeadView->value, 'admin'), 403);

        $lead = Lead::findOrFail($leadId);

        $lead->update(['status' => LeadStatus::Contacted]);

        AdminAuditLog::record('lead.contacted', ['email' => $lead->email], null);

        Flux::toast(__('Lead marqué « contacté ».'));
    }

    /**
     * Export CSV des leads filtrés (CdC § 13, acquisition) — BOM UTF-8 pour
     * compatibilité tableur.
     */
    public function export(): BinaryFileResponse
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::LeadView->value, 'admin'), 403);

        $leads = Lead::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%')
                    ->orWhere('phone', 'ilike', '%'.$this->search.'%')
                    ->orWhere('shop_name', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->orderByDesc('created_at')
            ->get();

        $csv = fopen('php://temp', 'w+');

        fwrite($csv, "\xEF\xBB\xBF");

        fputcsv($csv, ['ID', 'Nom', 'E-mail', 'Téléphone', 'Boutique', 'Source', 'Statut', 'Créé le']);

        foreach ($leads as $lead) {
            fputcsv($csv, [
                $lead->id,
                $lead->name,
                $lead->email,
                $lead->phone,
                $lead->shop_name,
                $lead->source,
                $lead->status->value,
                $lead->created_at?->format('Y-m-d H:i'),
            ]);
        }

        rewind($csv);

        $contents = stream_get_contents($csv);

        fclose($csv);

        $path = storage_path('app/exports/leads-'.now()->format('Ymd-His').'.csv');

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $contents);

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }
};
