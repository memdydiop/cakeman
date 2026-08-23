<?php

use App\Enums\AdminPermission;
use App\Models\AdminWebhookLog;
use App\Services\SubscriptionPaymentProcessor;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Webhooks')] class extends Component
{
    use WithPagination;

    private const REPLAYABLE_STATUSES = ['failed', 'mismatch'];

    public string $search = '';

    public int $perPage = 10;

    /** @var array{status: string} */
    public array $filters = [
        'status' => 'all',
    ];

    public ?int $payloadLogId = null;

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::WebhookReplay->value, 'admin'), 403);
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
        return AdminWebhookLog::query()
            ->with('invoice')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('provider_ref', 'ilike', '%'.$this->search.'%')
                    ->orWhere('provider', 'ilike', '%'.$this->search.'%')
                    ->orWhere('error', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('invoice', fn ($query) => $query->where('number', 'ilike', '%'.$this->search.'%'));
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function showPayload(int $logId): void
    {
        AdminWebhookLog::findOrFail($logId);

        $this->payloadLogId = $logId;

        Flux::modal('webhook-payload')->show();
    }

    #[Computed]
    public function payloadLog(): ?AdminWebhookLog
    {
        return $this->payloadLogId ? AdminWebhookLog::find($this->payloadLogId) : null;
    }

    /**
     * Rejeu manuel d'un webhook échoué ou en écart de montant (CdC § 3,
     * Module 12) : le payload stocké est retraité via le processeur, dans le
     * même log (statut mis à jour).
     */
    public function replay(int $logId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::WebhookReplay->value, 'admin'), 403);

        $log = AdminWebhookLog::findOrFail($logId);

        abort_unless($log->signature_valid && in_array($log->status, self::REPLAYABLE_STATUSES, true), 404);

        app(SubscriptionPaymentProcessor::class)->process(
            provider: $log->provider,
            event: $log->event,
            providerRef: $log->provider_ref,
            payload: $log->payload,
            signatureValid: true,
            existingLog: $log,
        );

        Flux::toast(__('Webhook rejoué.'));
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'processed' => 'green',
            'received' => 'blue',
            'duplicate' => 'zinc',
            'ignored' => 'zinc',
            'mismatch' => 'amber',
            default => 'red',
        };
    }
};
