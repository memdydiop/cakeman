<?php

use App\Enums\AdminPermission;
use App\Enums\InvoiceStatus;
use App\Models\AdminAuditLog;
use App\Models\Invoice;
use App\Services\SubscriptionPaymentProcessor;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Factures')] class extends Component
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
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);
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
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::query()
            ->with('subscription.plan', 'subscription.tenant')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('number', 'ilike', '%'.$this->search.'%')
                    ->orWhere('provider_ref', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('subscription.tenant', fn ($query) => $query->where('name', 'ilike', '%'.$this->search.'%'));
            }))
            ->when($this->filters['status'] !== 'all', fn ($query) => $query->where('status', $this->filters['status']))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function statusLabel(InvoiceStatus $status): string
    {
        return match ($status) {
            InvoiceStatus::Pending => 'En attente',
            InvoiceStatus::Paid => 'Payée',
            InvoiceStatus::Failed => 'Échouée',
            InvoiceStatus::Overdue => 'En retard',
            InvoiceStatus::Void => 'Annulée',
        };
    }

    public function statusColor(InvoiceStatus $status): string
    {
        return match ($status) {
            InvoiceStatus::Pending => 'amber',
            InvoiceStatus::Paid => 'green',
            InvoiceStatus::Failed => 'red',
            InvoiceStatus::Overdue => 'red',
            InvoiceStatus::Void => 'zinc',
        };
    }

    /**
     * Confirmation de paiement (sandbox) : même processeur que le webhook
     * réel (idempotent, montant vérifié) — réservé aux factures impayées.
     */
    public function simulatePayment(int $invoiceId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);

        $invoice = Invoice::with('subscription')->findOrFail($invoiceId);

        abort_unless(in_array($invoice->status, [InvoiceStatus::Pending, InvoiceStatus::Overdue], true), 404);

        app(SubscriptionPaymentProcessor::class)->process(
            provider: $invoice->method ?? config('mobile-money.provider', 'wave'),
            event: 'payment.succeeded',
            providerRef: $invoice->provider_ref,
            payload: [
                'provider' => $invoice->method ?? config('mobile-money.provider', 'wave'),
                'event' => 'payment.succeeded',
                'provider_ref' => $invoice->provider_ref,
                'amount' => $invoice->amount,
            ],
            signatureValid: true,
        );

        Flux::toast(__('Paiement confirmé — facture réglée.'));
    }

    public function remind(int $invoiceId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);

        $invoice = Invoice::with('subscription.tenant')->findOrFail($invoiceId);

        abort_unless(in_array($invoice->status, [InvoiceStatus::Pending, InvoiceStatus::Overdue], true), 404);

        AdminAuditLog::record('invoice.reminded', ['number' => $invoice->number], $invoice->subscription->tenant_id);

        Flux::toast(__('Relance envoyée pour la facture :number.', ['number' => $invoice->number]));
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
};
