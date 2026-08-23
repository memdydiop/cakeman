<?php

use App\Enums\AdminPermission;
use App\Models\AdminAuditLog;
use App\Models\Announcement;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Annonces')] class extends Component
{
    use WithPagination;

    public string $title = '';

    public string $body = '';

    #[Computed]
    public function announcements(): LengthAwarePaginator
    {
        return Announcement::orderByDesc('created_at')->paginate(15);
    }

    public function publish(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::SettingsManage->value, 'admin'), 403);

        $this->validate([
            'title' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        Announcement::create([
            'title' => $this->title,
            'body' => $this->body,
            'created_by' => auth('admin')->id(),
            'published_at' => now(),
        ]);

        AdminAuditLog::record('announcement.published', ['title' => $this->title]);

        $this->reset(['title', 'body']);
        Flux::modal('create-announcement')->close();
        Flux::toast(__('Annonce publiée.'));
    }

    public function delete(int $id): void
    {
        Announcement::findOrFail($id)->delete();
        AdminAuditLog::record('announcement.deleted', ['id' => $id]);
        Flux::toast(__('Annonce supprimée.'));
    }
};
