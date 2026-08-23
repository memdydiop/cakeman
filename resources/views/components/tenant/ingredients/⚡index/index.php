<?php

use App\Models\Ingredient;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Ingrédients')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $unit = 'kg';

    public float $stock_quantity = 0;

    public int $unit_cost = 0;

    public ?float $low_stock_threshold = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function ingredients(): LengthAwarePaginator
    {
        return Ingredient::query()
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function totalValorisation(): int
    {
        return (int) Ingredient::query()->selectRaw('SUM(stock_quantity * unit_cost) as total')->value('total');
    }

    public function create(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:10'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        Ingredient::create($validated);
        $this->reset(['name', 'stock_quantity', 'unit_cost', 'low_stock_threshold']);
        $this->unit = 'kg';
        Flux::modal('create-ingredient')->close();
        Flux::toast(__('Ingrédient créé.'));
    }

    public function adjustStock(int $id, float $delta): void
    {
        $ing = Ingredient::findOrFail($id);
        $ing->update(['stock_quantity' => max(0, (float) $ing->stock_quantity + $delta)]);
        Flux::toast(__('Stock ajusté.'));
    }

    public function delete(int $id): void
    {
        Ingredient::findOrFail($id)->delete();
        Flux::toast(__('Ingrédient supprimé.'));
    }
};
