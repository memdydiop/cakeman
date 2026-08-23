<?php

use App\Models\AdminLoginLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Journal de connexions')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $success = 'all';

    public int $perPage = 20;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSuccess(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return AdminLoginLog::query()
            ->with('adminUser')
            ->when($this->search, fn ($query) => $query->where('email', 'ilike', '%'.$this->search.'%'))
            ->when($this->success !== 'all', fn ($query) => $query->where('success', $this->success === 'success'))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }
};
