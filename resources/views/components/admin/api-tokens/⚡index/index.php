<?php

use App\Enums\AdminPermission;
use App\Models\AdminApiToken;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Clés API')] class extends Component
{
    use WithPagination;

    public string $name = '';

    public string $plainToken = '';

    #[Computed]
    public function tokens(): LengthAwarePaginator
    {
        return AdminApiToken::orderByDesc('created_at')->paginate(15);
    }

    public function create(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::SettingsManage->value, 'admin'), 403);

        $this->validate(['name' => ['required', 'string', 'max:191']]);

        $plain = Str::random(64);

        AdminApiToken::create([
            'name' => $this->name,
            'token' => hash('sha256', $plain),
            'created_by' => auth('admin')->id(),
        ]);

        $this->plainToken = $plain;
        $this->reset('name');
        Flux::toast(__('Clé API créée — copiez-la maintenant.'));
    }

    public function revoke(int $id): void
    {
        AdminApiToken::findOrFail($id)->delete();
        Flux::toast(__('Clé révoquée.'));
    }
};
