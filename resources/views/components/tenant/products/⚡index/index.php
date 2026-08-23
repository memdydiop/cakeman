<?php

use App\Models\Category;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Produits')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterCategory = null;

    public string $name = '';

    public ?int $category_id = null;

    public int $price = 0;

    public string $sku = '';

    public bool $is_active = true;

    public function mount(): void
    {
        // Gate pos not needed for catalogue, but could check products limit
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%')->orWhere('sku', 'ilike', '%'.$this->search.'%'))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function categoryOptions()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function create(): void
    {
        $tenant = tenant();
        if ($tenant) {
            $limit = $tenant->featureLimit('products.max');
            if ($limit !== null && Product::count() >= $limit) {
                Flux::toast(__('Limite atteinte : ').$limit.' produits max.', variant: 'danger');

                return;
            }
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'is_active' => ['boolean'],
        ]);

        Product::create($validated);
        $this->reset(['name', 'category_id', 'price', 'sku', 'is_active']);
        $this->is_active = true;
        Flux::modal('create-product')->close();
        Flux::toast(__('Produit créé.'));
    }

    public function toggle(int $id): void
    {
        $p = Product::findOrFail($id);
        $p->update(['is_active' => ! $p->is_active]);
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
        Flux::toast(__('Produit supprimé.'));
    }
};
