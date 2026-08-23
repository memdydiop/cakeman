<?php

use App\Models\TenantCoupon;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Promos')] class extends Component
{
    use WithPagination;

    public string $code = '';

    public string $type = 'percent';

    public int $value = 10;

    #[Computed]
    public function coupons(): LengthAwarePaginator
    {
        return TenantCoupon::orderByDesc('created_at')->paginate(15);
    }

    public function create(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:30', 'unique:tenant_coupons,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1'],
        ]);

        TenantCoupon::create($validated);
        $this->reset(['code', 'value']);
        $this->type = 'percent';
        Flux::modal('create-coupon')->close();
        Flux::toast(__('Coupon créé.'));
    }

    public function toggle(int $id): void
    {
        $c = TenantCoupon::findOrFail($id);
        $c->update(['is_active' => ! $c->is_active]);
    }

    public function delete(int $id): void
    {
        TenantCoupon::findOrFail($id)->delete();
        Flux::toast(__('Supprimé.'));
    }
};
