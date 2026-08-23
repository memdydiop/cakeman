<?php

use App\Enums\AdminPermission;
use App\Models\AdminAuditLog;
use App\Models\Coupon;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Coupons')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $code = '';

    public string $discount_type = 'percentage';

    public int $discount_value = 10;

    public bool $is_active = true;

    public ?int $max_uses = null;

    public ?string $expires_at = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function coupons(): LengthAwarePaginator
    {
        return Coupon::query()
            ->when($this->search, fn ($q) => $q->where('code', 'ilike', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'code', 'discount_type', 'discount_value', 'is_active', 'max_uses', 'expires_at']);
        $this->discount_type = 'percentage';
        $this->discount_value = 10;
        $this->is_active = true;

        Flux::modal('coupon-form')->show();
    }

    public function startEditing(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->discount_type = $coupon->discount_type;
        $this->discount_value = $coupon->discount_value;
        $this->is_active = $coupon->is_active;
        $this->max_uses = $coupon->max_uses;
        $this->expires_at = $coupon->expires_at?->format('Y-m-d\TH:i');

        Flux::modal('coupon-form')->show();
    }

    public function save(): void
    {
        abort_unless(auth('admin')->user()->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);

        $this->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/i', 'unique:coupons,code'.($this->editingId ? ','.$this->editingId : '')],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'integer', 'min:1', $this->discount_type === 'percentage' ? 'max:100' : 'max:1000000'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data = [
            'code' => strtoupper($this->code),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'is_active' => $this->is_active,
            'max_uses' => $this->max_uses,
            'expires_at' => $this->expires_at ? Carbon::parse($this->expires_at) : null,
        ];

        if ($this->editingId) {
            Coupon::findOrFail($this->editingId)->update($data);
            AdminAuditLog::record('coupon.updated', ['code' => $data['code']]);
        } else {
            Coupon::create($data);
            AdminAuditLog::record('coupon.created', ['code' => $data['code']]);
        }

        Flux::modal('coupon-form')->close();
        Flux::toast(__('Coupon enregistré.'));
    }

    public function toggle(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
        AdminAuditLog::record('coupon.toggled', ['code' => $coupon->code, 'is_active' => $coupon->is_active]);
        Flux::toast($coupon->is_active ? __('Coupon activé.') : __('Coupon désactivé.'));
    }
};
