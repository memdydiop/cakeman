<?php

use App\Models\Client;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Clients')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $phone = '';

    public ?string $email = null;

    public string $type = 'particulier';

    public ?string $preferences = null;

    public ?string $birthday = null;

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%')->orWhere('phone', 'ilike', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'type' => ['required', 'in:particulier,pro'],
            'preferences' => ['nullable', 'string', 'max:1000'],
            'birthday' => ['nullable', 'date'],
        ]);

        Client::create($validated);
        $this->reset(['name', 'phone', 'email', 'preferences', 'birthday']);
        $this->type = 'particulier';
        Flux::modal('create-client')->close();
        Flux::toast(__('Client créé.'));
    }

    public function toggle(int $id): void
    {
        $c = Client::findOrFail($id);
        $c->update(['is_active' => ! $c->is_active]);
    }

    public function delete(int $id): void
    {
        Client::findOrFail($id)->delete();
        Flux::toast(__('Client supprimé.'));
    }
};
