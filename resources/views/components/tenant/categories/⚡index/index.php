<?php

use App\Models\Category;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Catégories')] class extends Component
{
    public string $name = '';

    public ?int $parent_id = null;

    public ?int $editingId = null;

    public string $editingName = '';

    #[Computed]
    public function categories()
    {
        return Category::with('parent')->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function parentOptions()
    {
        return Category::whereNull('parent_id')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:100']]);

        Category::create(['name' => $this->name, 'parent_id' => $this->parent_id]);
        $this->reset(['name', 'parent_id']);
        Flux::modal('create-category')->close();
        Flux::toast(__('Catégorie créée.'));
    }

    public function startEdit(int $id): void
    {
        $cat = Category::findOrFail($id);
        $this->editingId = $id;
        $this->editingName = $cat->name;
        Flux::modal('edit-category')->show();
    }

    public function update(): void
    {
        $this->validate(['editingName' => ['required', 'string', 'max:100']]);
        Category::findOrFail($this->editingId)->update(['name' => $this->editingName]);
        Flux::modal('edit-category')->close();
        Flux::toast(__('Catégorie mise à jour.'));
    }

    public function toggle(int $id): void
    {
        $cat = Category::findOrFail($id);
        $cat->update(['is_active' => ! $cat->is_active]);
    }

    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
        Flux::toast(__('Catégorie supprimée.'));
    }
};
