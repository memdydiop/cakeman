<?php

use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Fournisseurs')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public ?string $phone = null;

    #[Computed]
    public function suppliers(): LengthAwarePaginator
    {
        return Supplier::query()->when($this->search, fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%'))->orderBy('name')->paginate(15);
    }

    public function create(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:20']]);
        Supplier::create(['name' => $this->name, 'phone' => $this->phone]);
        $this->reset(['name', 'phone']);
        Flux::modal('create-supplier')->close();
        Flux::toast(__('Fournisseur créé.'));
    }

    public function delete(int $id): void
    {
        Supplier::findOrFail($id)->delete();
        Flux::toast(__('Supprimé.'));
    }
};
