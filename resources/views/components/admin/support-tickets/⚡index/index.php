<?php

use App\Enums\AdminPermission;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\SupportTicket;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Tickets support')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var array{status: string, priority: string} */
    public array $filters = ['status' => 'all', 'priority' => 'all'];

    public string $ticketTenantId = '';

    public string $ticketSubject = '';

    public string $ticketBody = '';

    public string $ticketPriority = 'medium';

    public ?int $replyTicketId = null;

    public string $replyBody = '';

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SupportSession->value, 'admin'), 403);
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with(['tenant', 'creator', 'assignee'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('subject', 'ilike', '%'.$this->search.'%')
                    ->orWhere('body', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('tenant', fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%'));
            }))
            ->when($this->filters['status'] !== 'all', fn ($q) => $q->where('status', $this->filters['status']))
            ->when($this->filters['priority'] !== 'all', fn ($q) => $q->where('priority', $this->filters['priority']))
            ->orderByDesc('updated_at')
            ->paginate($this->perPage);
    }

    /** @return Collection<int, array{id: string, label: string}> */
    #[Computed]
    public function tenantOptions(): Collection
    {
        return Tenant::query()->whereIn('status', [TenantStatus::Active, TenantStatus::Trialing])
            ->orderBy('name')->get()
            ->map(fn (Tenant $t): array => ['id' => $t->id, 'label' => $t->name.' ('.($t->domains->first()?->domain ?? $t->id).')']);
    }

    public function createTicket(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SupportSession->value, 'admin'), 403);

        $validated = $this->validate([
            'ticketTenantId' => ['required', 'exists:tenants,id'],
            'ticketSubject' => ['required', 'string', 'min:5', 'max:200'],
            'ticketBody' => ['required', 'string', 'min:10', 'max:5000'],
            'ticketPriority' => ['required', 'in:low,medium,high'],
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $validated['ticketTenantId'],
            'created_by' => auth('admin')->id(),
            'subject' => $validated['ticketSubject'],
            'body' => $validated['ticketBody'],
            'priority' => $validated['ticketPriority'],
            'status' => 'open',
        ]);

        AdminAuditLog::record('support.ticket_created', ['ticket_id' => $ticket->id, 'subject' => $ticket->subject], $ticket->tenant_id);
        $this->reset(['ticketTenantId', 'ticketSubject', 'ticketBody', 'ticketPriority']);
        $this->ticketPriority = 'medium';
        Flux::modal('create-ticket')->close();
        Flux::toast(__('Ticket créé.'));
    }

    public function assignToMe(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update(['assigned_to' => auth('admin')->id(), 'status' => 'pending']);
        AdminAuditLog::record('support.ticket_assigned', ['ticket_id' => $ticket->id], $ticket->tenant_id);
        Flux::toast(__('Ticket assigné.'));
    }

    public function closeTicket(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        abort_unless($ticket->isOpen(), 404);
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        AdminAuditLog::record('support.ticket_closed', ['ticket_id' => $ticket->id], $ticket->tenant_id);
        Flux::toast(__('Ticket fermé.'));
    }

    public function openReply(int $ticketId): void
    {
        $this->replyTicketId = $ticketId;
        $this->replyBody = '';
        Flux::modal('reply-ticket')->show();
    }

    public function addReply(): void
    {
        $this->validate(['replyBody' => ['required', 'string', 'min:2', 'max:5000']]);
        $ticket = SupportTicket::findOrFail($this->replyTicketId);
        $ticket->messages()->create(['admin_user_id' => auth('admin')->id(), 'body' => $this->replyBody]);
        $ticket->update(['status' => $ticket->status === 'closed' ? 'closed' : 'pending', 'updated_at' => now()]);
        AdminAuditLog::record('support.ticket_replied', ['ticket_id' => $ticket->id], $ticket->tenant_id);
        Flux::modal('reply-ticket')->close();
        $this->reset(['replyTicketId', 'replyBody']);
        Flux::toast(__('Réponse ajoutée.'));
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'Ouvert',
            'pending' => 'En cours',
            'closed' => 'Fermé',
            default => $status,
        };
    }

    public function priorityColor(string $priority): string
    {
        return match ($priority) {
            'high' => 'red',
            'medium' => 'amber',
            'low' => 'zinc',
            default => 'zinc',
        };
    }
};
