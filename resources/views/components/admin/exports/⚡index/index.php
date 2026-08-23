<?php

use App\Enums\AdminPermission;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Exports')] class extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);
    }

    /** @return list<string> */
    #[Computed]
    public function storedExports(): array
    {
        $files = Storage::disk('local')->files('exports');

        rsort($files);

        return $files;
    }
};
