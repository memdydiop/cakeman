<?php

use App\Enums\AdminPermission;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use App\Models\PlanAddon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('Plans & add-ons')] class extends Component
{
    public ?int $editingPlanId = null;

    public string $planName = '';

    public int $planMonthlyPrice = 0;

    public int $planAnnualPrice = 0;

    public ?int $planUsersLimit = null;

    public ?int $planOrdersLimit = null;

    public ?int $planStorageLimit = null;

    public string $planFeatures = '';

    public ?int $editingAddonId = null;

    public string $addonName = '';

    public int $addonMonthlyPrice = 0;

    public ?string $addonUnit = null;

    public function mount(): void
    {
        abort_unless(Auth::guard('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::orderBy('sort_order')->get();
    }

    #[Computed]
    public function addons(): Collection
    {
        return PlanAddon::orderBy('name')->get();
    }

    public function startEditingPlan(?int $id): void
    {
        $plan = $id ? Plan::findOrFail($id) : null;

        $this->editingPlanId = $plan?->id;
        $this->planName = $plan?->name ?? '';
        $this->planMonthlyPrice = $plan?->monthly_price ?? 0;
        $this->planAnnualPrice = $plan?->annual_price ?? 0;
        $this->planUsersLimit = $plan?->limits['users'] ?? null;
        $this->planOrdersLimit = $plan?->limits['orders_per_month'] ?? null;
        $this->planStorageLimit = $plan?->limits['storage_gb'] ?? null;
        $this->planFeatures = implode("\n", $plan?->features ?? []);

        Flux::modal('edit-plan')->show();
    }

    public function savePlan(): void
    {
        $this->validate([
            'planName' => ['required', 'string', 'max:255'],
            'planMonthlyPrice' => ['required', 'integer', 'min:0'],
            'planAnnualPrice' => ['required', 'integer', 'min:0'],
            'planUsersLimit' => ['nullable', 'integer', 'min:0'],
            'planOrdersLimit' => ['nullable', 'integer', 'min:0'],
            'planStorageLimit' => ['nullable', 'integer', 'min:0'],
        ]);

        $features = array_values(array_filter(
            array_map('trim', explode("\n", $this->planFeatures)),
            fn (string $line): bool => $line !== '',
        ));

        $limits = [
            'users' => $this->planUsersLimit,
            'orders_per_month' => $this->planOrdersLimit,
            'storage_gb' => $this->planStorageLimit,
        ];

        if ($this->editingPlanId) {
            $plan = Plan::findOrFail($this->editingPlanId);

            $plan->update([
                'name' => $this->planName,
                'monthly_price' => $this->planMonthlyPrice,
                'annual_price' => $this->planAnnualPrice,
                'limits' => $limits,
                'features' => $features,
            ]);

            AdminAuditLog::record('plan.updated', ['code' => $plan->code, 'name' => $plan->name]);
        } else {
            $code = Str::slug($this->planName);

            if ($code === '' || Plan::where('code', $code)->exists()) {
                throw ValidationException::withMessages([
                    'planName' => __('Impossible de générer un code unique à partir de ce nom.'),
                ]);
            }

            $plan = Plan::create([
                'code' => $code,
                'name' => $this->planName,
                'monthly_price' => $this->planMonthlyPrice,
                'annual_price' => $this->planAnnualPrice,
                'limits' => $limits,
                'features' => $features,
                'sort_order' => Plan::max('sort_order') + 10,
            ]);

            AdminAuditLog::record('plan.created', ['code' => $plan->code, 'name' => $plan->name]);
        }

        $this->reset([
            'editingPlanId',
            'planName',
            'planMonthlyPrice',
            'planAnnualPrice',
            'planUsersLimit',
            'planOrdersLimit',
            'planStorageLimit',
            'planFeatures',
        ]);

        Flux::toast(__('Plan enregistré.'));

        Flux::modal('edit-plan')->close();
    }

    public function togglePlan(int $id): void
    {
        $plan = Plan::findOrFail($id);

        $plan->update(['is_active' => ! $plan->is_active]);

        AdminAuditLog::record('plan.toggled', ['code' => $plan->code, 'is_active' => $plan->is_active]);

        Flux::toast($plan->is_active ? __('Plan activé.') : __('Plan désactivé.'));
    }

    public function startEditingAddon(?int $id): void
    {
        $addon = $id ? PlanAddon::findOrFail($id) : null;

        $this->editingAddonId = $addon?->id;
        $this->addonName = $addon?->name ?? '';
        $this->addonMonthlyPrice = $addon?->monthly_price ?? 0;
        $this->addonUnit = $addon?->unit;

        Flux::modal('edit-addon')->show();
    }

    public function saveAddon(): void
    {
        $this->validate([
            'addonName' => ['required', 'string', 'max:255'],
            'addonMonthlyPrice' => ['required', 'integer', 'min:0'],
            'addonUnit' => ['nullable', 'string', 'max:50'],
        ]);

        if ($this->editingAddonId) {
            $addon = PlanAddon::findOrFail($this->editingAddonId);

            $addon->update([
                'name' => $this->addonName,
                'monthly_price' => $this->addonMonthlyPrice,
                'unit' => $this->addonUnit,
            ]);

            AdminAuditLog::record('addon.updated', ['code' => $addon->code, 'name' => $addon->name]);
        } else {
            $code = Str::slug($this->addonName);

            if ($code === '' || PlanAddon::where('code', $code)->exists()) {
                throw ValidationException::withMessages([
                    'addonName' => __('Impossible de générer un code unique à partir de ce nom.'),
                ]);
            }

            $addon = PlanAddon::create([
                'code' => $code,
                'name' => $this->addonName,
                'monthly_price' => $this->addonMonthlyPrice,
                'unit' => $this->addonUnit,
            ]);

            AdminAuditLog::record('addon.created', ['code' => $addon->code, 'name' => $addon->name]);
        }

        $this->reset(['editingAddonId', 'addonName', 'addonMonthlyPrice', 'addonUnit']);

        Flux::toast(__('Add-on enregistré.'));

        Flux::modal('edit-addon')->close();
    }

    public function toggleAddon(int $id): void
    {
        $addon = PlanAddon::findOrFail($id);

        $addon->update(['is_active' => ! $addon->is_active]);

        AdminAuditLog::record('addon.toggled', ['code' => $addon->code, 'is_active' => $addon->is_active]);

        Flux::toast($addon->is_active ? __('Add-on activé.') : __('Add-on désactivé.'));
    }
};
