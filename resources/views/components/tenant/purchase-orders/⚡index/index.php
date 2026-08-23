<?php

use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Achats')] class extends Component
{
    use WithPagination;

    public ?int $supplier_id = null;

    public ?int $ingredient_id = null;

    public float $quantity = 1;

    public int $unit_cost = 0;

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return PurchaseOrder::with(['supplier', 'ingredient'])->orderByDesc('created_at')->paginate(15);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function ingredients()
    {
        return Ingredient::orderBy('name')->get();
    }

    public function create(): void
    {
        $validated = $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'integer', 'min:0'],
        ]);

        PurchaseOrder::create([
            'supplier_id' => $validated['supplier_id'],
            'ingredient_id' => $validated['ingredient_id'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'total_cost' => (int) round($validated['quantity'] * $validated['unit_cost']),
            'status' => 'pending',
        ]);

        $this->reset(['supplier_id', 'ingredient_id', 'quantity', 'unit_cost']);
        $this->quantity = 1;
        Flux::modal('create-purchase')->close();
        Flux::toast(__('Commande fournisseur créée.'));
    }

    public function receive(int $id): void
    {
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status === 'received') {
            return;
        }
        $po->update(['status' => 'received', 'received_at' => now()]);
        $ing = $po->ingredient;
        $oldQty = (float) $ing->stock_quantity;
        $oldCost = (int) $ing->unit_cost;
        $newQty = (float) $po->quantity;
        $totalQty = $oldQty + $newQty;
        $avgCost = $totalQty > 0 ? (int) round(($oldQty * $oldCost + $newQty * (int) $po->unit_cost) / $totalQty) : (int) $po->unit_cost;
        $ing->update(['stock_quantity' => $totalQty, 'unit_cost' => $avgCost]);
        Flux::toast(__('Réception — stock à ').number_format($totalQty, 2, ',', ' ').$ing->unit.' @ '.number_format($avgCost, 0, ',', ' ').' FCFA/u');
    }

    public function cancel(int $id): void
    {
        PurchaseOrder::findOrFail($id)->update(['status' => 'cancelled']);
        Flux::toast(__('Annulée.'));
    }
};
