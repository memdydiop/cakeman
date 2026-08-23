<?php

use App\Enums\AdminPermission;
use App\Models\EmailTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Templates email')] class extends Component
{
    public ?int $editingId = null;

    public string $subject = '';

    public string $body = '';

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::SettingsManage->value, 'admin'), 403);
    }

    #[Computed]
    public function templates()
    {
        return EmailTemplate::orderBy('key')->get();
    }

    public function edit(int $id): void
    {
        $t = EmailTemplate::findOrFail($id);
        $this->editingId = $id;
        $this->subject = $t->subject;
        $this->body = $t->body;
        Flux::modal('edit-template')->show();
    }

    public function save(): void
    {
        $this->validate(['subject' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:5000']]);
        EmailTemplate::findOrFail($this->editingId)->update(['subject' => $this->subject, 'body' => $this->body]);
        Flux::modal('edit-template')->close();
        Flux::toast(__('Template mis à jour.'));
    }
};
