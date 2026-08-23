<?php

use App\Models\Order;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Commande')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['client', 'items.product', 'items.product.recipe']);
    }

    #[Computed]
    public function timeline(): array
    {
        $flow = Order::class ? Order::class : [];
        $steps = ['pending' => 'En attente', 'confirmed' => 'Confirmée', 'in_production' => 'En prod', 'ready' => 'Prête', 'delivered' => 'Livrée'];
        $currentIdx = array_search($this->order->status, array_keys($steps), true);

        return ['steps' => $steps, 'currentIdx' => $currentIdx === false ? -1 : $currentIdx];
    }

    public function advance(): void
    {
        $flow = ['pending', 'confirmed', 'in_production', 'ready', 'delivered'];
        $idx = array_search($this->order->status, $flow, true);
        if ($idx === false || $idx === count($flow) - 1) {
            return;
        }
        $next = $flow[$idx + 1];

        // Décrément stock à la confirmation (première fois)
        if ($this->order->status === 'pending' && $next === 'confirmed') {
            foreach ($this->order->items as $item) {
                $recipe = $item->product->recipe ?? null;
                if (! $recipe) {
                    continue;
                }
                $recipe->loadMissing('ingredients.ingredient');
                foreach ($recipe->ingredients as $ri) {
                    $ing = $ri->ingredient;
                    $need = (float) $ri->quantity * $item->quantity;
                    $ing->update(['stock_quantity' => max(0, (float) $ing->stock_quantity - $need)]);
                }
            }
        }

        $this->order->update(['status' => $next]);
        Flux::toast(__('Statut : ').$next);

        if ($next === 'delivered') {
            $this->order->update(['delivered_at' => now()]);
        }
    }

    public function duplicate(): void
    {
        $new = $this->order->replicate(['status', 'delivered_at', 'delivery_photo_path', 'delivery_signature']);
        $new->status = 'pending';
        $new->delivered_at = null;
        $new->delivery_photo_path = null;
        $new->delivery_signature = null;
        $new->save();

        foreach ($this->order->items as $item) {
            $new->items()->create($item->only(['product_id', 'quantity', 'unit_price', 'line_total', 'customization']));
        }

        $this->redirect(route('tenant.orders.show', $new), navigate: true);
        Flux::toast(__('Commande dupliquée.'));
    }
};
