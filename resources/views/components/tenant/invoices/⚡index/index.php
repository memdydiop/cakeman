<?php

use App\Models\Order;
use App\Models\TenantInvoice;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Factures')] class extends Component
{
    use WithPagination;

    public ?int $order_id = null;

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return TenantInvoice::with(['client', 'order'])->orderByDesc('created_at')->paginate(15);
    }

    #[Computed]
    public function pendingOrders()
    {
        return Order::whereDoesntHave('tenantInvoice')->where('status', '!=', 'cancelled')->orderByDesc('created_at')->get();
    }

    public function generate(): void
    {
        $this->validate(['order_id' => ['required', 'exists:orders,id']]);
        $order = Order::with('client')->findOrFail($this->order_id);

        if ($order->tenantInvoice) {
            Flux::toast(__('Facture déjà existante.'), variant: 'danger');

            return;
        }

        $number = 'FAC-'.now()->format('Ymd').'-'.str_pad((string) (TenantInvoice::count() + 1), 4, '0', STR_PAD_LEFT);

        TenantInvoice::create([
            'number' => $number,
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'amount' => $order->total,
            'status' => 'draft',
            'due_at' => $order->due_at,
        ]);

        $this->reset('order_id');
        Flux::modal('create-invoice')->close();
        Flux::toast(__('Facture générée.'));
    }

    public function markPaid(int $id): void
    {
        TenantInvoice::findOrFail($id)->update(['status' => 'paid', 'paid_at' => now()]);
        Flux::toast(__('Facture payée.'));
    }

    public function cancel(int $id): void
    {
        TenantInvoice::findOrFail($id)->update(['status' => 'cancelled']);
        Flux::toast(__('Facture annulée.'));
    }
};
