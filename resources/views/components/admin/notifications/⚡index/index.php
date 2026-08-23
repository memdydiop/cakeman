<?php

use App\Enums\AdminPermission;
use App\Models\AdminNotification;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Notifications')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SettingsManage->value, 'admin'), 403);
    }

    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return AdminNotification::with('adminUser')->latest()->paginate(15);
    }

    public function markRead(int $id): void
    {
        AdminNotification::findOrFail($id)->update(['read_at' => now()]);
        Flux::toast(__('Marqué lu.'));
    }

    public function markAllRead(): void
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        Flux::toast(__('Toutes marquées lues.'));
    }
};
