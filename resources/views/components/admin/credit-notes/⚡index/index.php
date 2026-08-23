<?php

use App\Enums\AdminPermission;
use App\Models\AdminAuditLog;
use App\Models\CreditNote;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Avoirs')] class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $tenantId = '';

    public ?int $invoiceId = null;

    public int $amount = 0;

    public string $reason = '';

    #[Computed]
    public function creditNotes(): LengthAwarePaginator
    {
        return CreditNote::orderByDesc('created_at')->paginate(15);
    }

    #[Computed]
    public function tenants(): Collection
    {
        return Tenant::orderBy('name')->get();
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'tenantId', 'invoiceId', 'amount', 'reason']);
        Flux::modal('credit-note-form')->show();
    }

    public function save(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);

        $this->validate([
            'tenantId' => ['required', 'exists:tenants,id'],
            'invoiceId' => ['nullable', 'exists:invoices,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        CreditNote::create([
            'tenant_id' => $this->tenantId,
            'invoice_id' => $this->invoiceId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'created_by' => auth('admin')->id(),
        ]);

        AdminAuditLog::record('credit_note.created', ['amount' => $this->amount, 'tenant_id' => $this->tenantId]);
        Flux::modal('credit-note-form')->close();
        Flux::toast(__('Avoir enregistré.'));
    }
};
