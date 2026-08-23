<?php

use App\Enums\AdminPermission;
use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AdminAuditLog;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanAddon;
use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\Tenant;
use App\Services\SubscriptionPaymentProcessor;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] #[Title('Abonnements')] class extends Component
{
    use WithPagination;

    public ?string $editingTenantId = null;

    public string $planCode = 'starter';

    public string $billingPeriod = BillingPeriod::Monthly->value;

    public ?int $couponId = null;

    public ?string $invoicesTenantId = null;

    public ?string $addonsTenantId = null;

    /** @var array<int, int> */
    public array $addonQuantities = [];

    public function mount(): void
    {
        abort_unless(Auth::guard('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);
    }

    #[Computed]
    public function tenants(): LengthAwarePaginator
    {
        return Tenant::with('subscription.plan')->orderBy('name')->paginate(15);
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function addons(): Collection
    {
        return PlanAddon::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function validCoupons(): Collection
    {
        return Coupon::where('is_active', true)->orderBy('code')->get();
    }

    #[Computed]
    public function mrr(): int
    {
        $subscriptionsMrr = Subscription::with('plan')
            ->whereIn('status', [
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->get()
            ->sum(
                fn (Subscription $subscription): int => $subscription->billing_period === BillingPeriod::Annual
                    ? (int) round($subscription->plan->annual_price / 12)
                    : $subscription->plan->monthly_price,
            );

        $addonsMrr = SubscriptionAddon::query()
            ->whereNull('removed_at')
            ->whereHas('subscription', fn ($query) => $query->whereIn('status', [
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::PastDue->value,
            ]))
            ->with('planAddon')
            ->get()
            ->sum(fn (SubscriptionAddon $addon): int => $addon->quantity * $addon->planAddon->monthly_price);

        return $subscriptionsMrr + $addonsMrr;
    }

    #[Computed]
    public function activeCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::Active->value)->count();
    }

    #[Computed]
    public function trialingCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::Trialing->value)->count();
    }

    #[Computed]
    public function pastDueCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::PastDue->value)->count();
    }

    #[Computed]
    public function invoices(): Collection
    {
        return $this->invoicesTenantId
            ? Invoice::where('tenant_id', $this->invoicesTenantId)->orderByDesc('created_at')->get()
            : new Collection;
    }

    public function startEditing(string $tenantId): void
    {
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        $this->editingTenantId = $tenantId;
        $this->planCode = $subscription?->plan->code ?? 'starter';
        $this->billingPeriod = $subscription?->billing_period->value ?? BillingPeriod::Monthly->value;
        $this->couponId = $subscription?->coupon_id;

        Flux::modal('edit-subscription')->show();
    }

    /**
     * Enregistrement d'un abonnement (Module 12) : nouvel abonnement =
     * essai 14 jours sans carte ; changement de plan = nouvelle facture ;
     * plan Gratuit = annulation.
     */
    public function saveSubscription(): void
    {
        $this->validate([
            'planCode' => ['required', 'exists:plans,code'],
            'billingPeriod' => ['required', 'in:'.BillingPeriod::Monthly->value.','.BillingPeriod::Annual->value],
            'couponId' => ['nullable', 'exists:coupons,id'],
        ]);

        $coupon = $this->couponId ? Coupon::find($this->couponId) : null;

        if ($coupon && ! $coupon->isValid()) {
            throw ValidationException::withMessages(['couponId' => __('Ce coupon n\'est plus valide.')]);
        }

        $tenant = Tenant::findOrFail($this->editingTenantId);
        $plan = Plan::where('code', $this->planCode)->firstOrFail();
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();

        if ($plan->code === 'free') {
            if ($subscription && $subscription->status !== SubscriptionStatus::Cancelled) {
                $subscription->update([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancelled_at' => now(),
                ]);
            }

            AdminAuditLog::record('subscription.cancelled', ['plan' => $plan->name], $tenant->id);

            Flux::toast(__('Abonnement annulé (plan Gratuit).'));

            Flux::modal('edit-subscription')->close();

            return;
        }

        $wasCancelled = $subscription?->status === SubscriptionStatus::Cancelled;
        $previousCouponId = $subscription?->coupon_id;

        if (! $subscription) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_period' => $this->billingPeriod,
                'coupon_id' => $this->couponId,
                'status' => SubscriptionStatus::Trialing,
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => now()->addDays(14),
                'current_period_end' => $this->periodEndFrom(now()->addDays(14)),
            ]);
        } else {
            $subscription->update([
                'plan_id' => $plan->id,
                'billing_period' => $this->billingPeriod,
                'coupon_id' => $this->couponId,
            ]);

            if ($wasCancelled) {
                $subscription->update([
                    'status' => SubscriptionStatus::Trialing,
                    'cancelled_at' => null,
                    'trial_ends_at' => now()->addDays(14),
                    'current_period_start' => now()->addDays(14),
                    'current_period_end' => $this->periodEndFrom(now()->addDays(14)),
                ]);
            }
        }

        if ($coupon && $previousCouponId !== $coupon->id) {
            $coupon->increment('used_count');
        }

        $this->createInvoice($tenant, $subscription->fresh(), $plan);

        AdminAuditLog::record(
            'subscription.updated',
            ['plan' => $plan->name, 'period' => $this->billingPeriod],
            $tenant->id,
        );

        Flux::toast(__('Abonnement enregistré.'));

        Flux::modal('edit-subscription')->close();
    }

    public function cancel(string $tenantId): void
    {
        $subscription = Subscription::where('tenant_id', $tenantId)->firstOrFail();

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        AdminAuditLog::record('subscription.cancelled', ['plan' => $subscription->plan->name], $tenantId);

        Flux::toast(__('Abonnement annulé.'));
    }

    public function showInvoices(string $tenantId): void
    {
        $this->invoicesTenantId = $tenantId;

        Flux::modal('tenant-invoices')->show();
    }

    /**
     * Simulation de paiement (sandbox) : le lien Mobile Money est confirmé
     * via le même processeur que le webhook réel (idempotent, montant vérifié).
     */
    public function simulatePayment(int $invoiceId): void
    {
        abort_unless(auth('admin')->user()?->hasPermissionTo(AdminPermission::BillingManage->value, 'admin'), 403);

        $invoice = Invoice::with('subscription')->findOrFail($invoiceId);

        abort_unless(in_array($invoice->status, [InvoiceStatus::Pending, InvoiceStatus::Overdue], true), 404);

        app(SubscriptionPaymentProcessor::class)->process(
            provider: $invoice->method ?? config('mobile-money.provider', 'wave'),
            event: 'payment.succeeded',
            providerRef: $invoice->provider_ref,
            payload: [
                'provider' => $invoice->method ?? config('mobile-money.provider', 'wave'),
                'event' => 'payment.succeeded',
                'provider_ref' => $invoice->provider_ref,
                'amount' => $invoice->amount,
            ],
            signatureValid: true,
        );

        Flux::toast(__('Paiement confirmé — facture réglée.'));
    }

    public function showAddons(string $tenantId): void
    {
        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        $this->addonsTenantId = $tenantId;
        $this->addonQuantities = $subscription
            ? SubscriptionAddon::where('subscription_id', $subscription->id)
                ->whereNull('removed_at')
                ->pluck('quantity', 'plan_addon_id')
                ->map(fn ($quantity): int => (int) $quantity)
                ->all()
            : [];

        Flux::modal('tenant-addons')->show();
    }

    /**
     * Enregistrement des add-ons (Module 12) : facture au prorata de la
     * période en cours pour les quantités ajoutées, retrait journalisé.
     */
    public function saveAddons(): void
    {
        $tenant = Tenant::findOrFail($this->addonsTenantId);
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();

        if (! $subscription || $subscription->status === SubscriptionStatus::Cancelled) {
            throw ValidationException::withMessages([
                'addons' => __('Activez d\'abord un plan payant pour cette boutique.'),
            ]);
        }

        $this->validate([
            'addonQuantities' => ['array'],
            'addonQuantities.*' => ['integer', 'min:0', 'max:100'],
        ]);

        $prorata = 0;
        $periodDays = $subscription->billing_period === BillingPeriod::Annual ? 365 : 30;

        foreach ($this->addons as $addon) {
            $quantity = (int) ($this->addonQuantities[$addon->id] ?? 0);
            $existing = SubscriptionAddon::where('subscription_id', $subscription->id)
                ->where('plan_addon_id', $addon->id)
                ->whereNull('removed_at')
                ->first();

            if ($quantity === 0) {
                if ($existing) {
                    $existing->update(['removed_at' => now()]);
                }

                continue;
            }

            $previousQuantity = $existing?->quantity ?? 0;

            if ($existing) {
                $existing->update(['quantity' => $quantity]);
            } else {
                SubscriptionAddon::create([
                    'subscription_id' => $subscription->id,
                    'plan_addon_id' => $addon->id,
                    'quantity' => $quantity,
                    'added_at' => now(),
                ]);
            }

            if ($quantity > $previousQuantity) {
                $remaining = max(0, (int) now()->diffInDays($subscription->current_period_end ?? now()->addDays(30)));
                $prorata += (int) round(($quantity - $previousQuantity) * $addon->monthly_price * $remaining / $periodDays);
            }
        }

        if ($prorata > 0) {
            $invoice = Invoice::create([
                'number' => 'TMP-'.$subscription->id,
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => $prorata,
                'status' => InvoiceStatus::Pending,
                'due_at' => now()->addDays(7),
            ]);

            $invoice->update([
                'number' => 'FACT-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        AdminAuditLog::record('subscription.addons_updated', ['addons' => $this->addonQuantities, 'prorata' => $prorata], $tenant->id);

        Flux::toast(__('Add-ons enregistrés.'));

        Flux::modal('tenant-addons')->close();
    }

    public function statusLabel(?SubscriptionStatus $status): string
    {
        return match ($status) {
            SubscriptionStatus::Trialing => 'Essai',
            SubscriptionStatus::Active => 'Actif',
            SubscriptionStatus::PastDue => 'En retard',
            SubscriptionStatus::Cancelled => 'Annulé',
            default => '—',
        };
    }

    public function invoiceStatusLabel(InvoiceStatus $status): string
    {
        return match ($status) {
            InvoiceStatus::Pending => 'En attente',
            InvoiceStatus::Paid => 'Payée',
            InvoiceStatus::Failed => 'Échouée',
            InvoiceStatus::Overdue => 'En retard',
            InvoiceStatus::Void => 'Annulée',
        };
    }

    public function periodLabel(?BillingPeriod $period): string
    {
        return match ($period) {
            BillingPeriod::Monthly => 'Mensuel',
            BillingPeriod::Annual => 'Annuel',
            default => '—',
        };
    }

    private function periodEndFrom(CarbonInterface $start): CarbonInterface
    {
        return $this->billingPeriod === BillingPeriod::Annual->value
            ? $start->addYear()
            : $start->addMonth();
    }

    private function createInvoice(Tenant $tenant, Subscription $subscription, Plan $plan): void
    {
        $base = $subscription->billing_period === BillingPeriod::Annual ? $plan->annual_price : $plan->monthly_price;
        $amount = $base;

        if ($subscription->coupon_id) {
            $coupon = Coupon::find($subscription->coupon_id);

            if ($coupon && $coupon->isValid()) {
                if ($coupon->discount_type === 'percentage') {
                    $amount = (int) round($base * (100 - $coupon->discount_value) / 100);
                } else {
                    $amount = max(0, $base - $coupon->discount_value);
                }
            }
        }

        $invoice = Invoice::create([
            'number' => 'TMP-'.$subscription->id,
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => InvoiceStatus::Pending,
            'due_at' => now()->addDays(7),
        ]);

        $invoice->update([
            'number' => 'FACT-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
        ]);
    }
};
