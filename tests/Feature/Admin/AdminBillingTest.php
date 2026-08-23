<?php

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\BillingReminderMail;
use App\Models\AdminUser;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanAddon;
use App\Models\Subscription;
use App\Models\SubscriptionAddon;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

test('plans seeder provisions the four CDC plans and add-ons', function () {
    expect(Plan::count())->toBe(4)
        ->and(Plan::where('code', 'starter')->firstOrFail()->monthly_price)->toBe(5000)
        ->and(Plan::where('code', 'pro')->firstOrFail()->annual_price)->toBe(120000);
});

test('billing role can access the subscriptions page', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->assertOk()
        ->assertSee('Abonnements');
});

test('support cannot access the subscriptions page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.subscriptions.index')
        ->assertForbidden();
});

test('starting a subscription creates a 14-day trial and a pending invoice', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant(['name' => 'Pâtisserie Abonnée']);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->assertSee('Pâtisserie Abonnée')
        ->call('startEditing', $tenant->id)
        ->set('planCode', 'pro')
        ->set('billingPeriod', 'monthly')
        ->call('saveSubscription')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'edit-subscription');

    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->plan->code)->toBe('pro')
        ->and($subscription->trial_ends_at->isFuture())->toBeTrue();

    $invoice = Invoice::where('tenant_id', $tenant->id)->firstOrFail();

    expect($invoice->amount)->toBe(12500)
        ->and($invoice->number)->toStartWith('FACT-');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'subscription.updated',
        'tenant_id' => $tenant->id,
    ]);
});

test('changing to the free plan cancels the subscription', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant();

    $plan = Plan::where('code', 'starter')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('startEditing', $tenant->id)
        ->set('planCode', 'free')
        ->call('saveSubscription')
        ->assertHasNoErrors();

    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancelled_at)->not->toBeNull();
});

test('invoices modal lists tenant invoices', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addMonth(),
    ]);

    Invoice::create([
        'number' => 'FACT-2026-00042',
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'amount' => 5000,
        'due_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('showInvoices', $tenant->id)
        ->assertDispatched('modal-show', name: 'tenant-invoices')
        ->assertSee('FACT-2026-00042')
        ->assertSee('5 000 FCFA');
});

test('billing:check-due generates an invoice with a payment link at trial end', function () {
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => now()->subDay(),
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->subDay()->addMonth(),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->grace_until->isFuture())->toBeTrue()
        ->and($subscription->current_period_end->isFuture())->toBeTrue();

    $invoice = Invoice::where('tenant_id', $tenant->id)->firstOrFail();

    expect($invoice->status)->toBe(InvoiceStatus::Pending)
        ->and($invoice->amount)->toBe(5000)
        ->and($invoice->payment_link_url)->toContain('pay.cakeman.test')
        ->and($invoice->provider_ref)->not->toBeNull()
        ->and($invoice->method)->toBe('wave');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'invoice.created',
        'tenant_id' => $tenant->id,
    ]);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'subscription.trial_ended',
        'tenant_id' => $tenant->id,
    ]);
});

test('billing:check-due suspends a tenant past its grace period', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);
    $plan = Plan::where('code', 'pro')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'current_period_end' => now()->subDays(10),
        'grace_until' => now()->subDay(),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'subscription.suspended',
        'tenant_id' => $tenant->id,
    ]);
});

test('billing:check-due moves an active subscription to past due at period end', function () {
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'business')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Annual,
        'status' => SubscriptionStatus::Active,
        'current_period_start' => now()->subYear(),
        'current_period_end' => now()->subDay(),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->grace_until->isFuture())->toBeTrue();
});

test('saving add-ons invoices the prorated amount', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_start' => now()->subDays(15),
        'current_period_end' => now()->addDays(15),
    ]);

    $addon = PlanAddon::where('code', 'extra_user')->firstOrFail();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('showAddons', $tenant->id)
        ->set('addonQuantities.'.$addon->id, 2)
        ->call('saveAddons')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'tenant-addons');

    $subscriptionAddon = SubscriptionAddon::where('subscription_id', Subscription::where('tenant_id', $tenant->id)->firstOrFail()->id)
        ->where('plan_addon_id', $addon->id)
        ->firstOrFail();

    expect($subscriptionAddon->quantity)->toBe(2)
        ->and($subscriptionAddon->removed_at)->toBeNull();

    $invoice = Invoice::where('tenant_id', $tenant->id)->firstOrFail();

    expect($invoice->amount)->toBeGreaterThan(0)
        ->and($invoice->amount)->toBeLessThanOrEqual(3000);

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'subscription.addons_updated',
        'tenant_id' => $tenant->id,
    ]);
});

test('add-ons require an active subscription', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->call('showAddons', $tenant->id)
        ->call('saveAddons')
        ->assertHasErrors(['addons']);
});

test('plans page lets billing admins edit a plan and the landing reflects it', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $pro = Plan::where('code', 'pro')->firstOrFail();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.plans.index')
        ->assertOk()
        ->assertSee('Pro')
        ->call('startEditingPlan', $pro->id)
        ->set('planMonthlyPrice', 15000)
        ->set('planFeatures', "10 utilisateurs\nCommandes illimitées")
        ->call('savePlan')
        ->assertHasNoErrors();

    expect($pro->fresh()->monthly_price)->toBe(15000)
        ->and($pro->fresh()->features)->toBe(['10 utilisateurs', 'Commandes illimitées']);

    $this->get(route('home'))
        ->assertSee('15 000 FCFA')
        ->assertSee('Commandes illimitées');
});

test('deactivating a plan hides it from the landing', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $starter = Plan::where('code', 'starter')->firstOrFail();

    Livewire::actingAs($billing, 'admin')
        ->test('admin.plans.index')
        ->call('togglePlan', $starter->id)
        ->assertHasNoErrors();

    expect($starter->fresh()->is_active)->toBeFalse();

    $this->get(route('home'))->assertDontSee('Starter');
});

test('billing admins can create an add-on visible on the landing', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.plans.index')
        ->set('addonName', 'Support prioritaire')
        ->set('addonMonthlyPrice', 25000)
        ->set('addonUnit', 'month')
        ->call('saveAddon')
        ->assertHasNoErrors();

    $addon = PlanAddon::where('code', 'support-prioritaire')->firstOrFail();

    expect($addon->monthly_price)->toBe(25000);

    $this->get(route('home'))->assertSee('25 000 FCFA');
});

test('support cannot access the plans page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.plans.index')
        ->assertForbidden();
});

test('subscriptions page computes mrr from plans and add-ons', function () {
    $billing = AdminUser::factory()->create();
    $billing->assignRole(AdminRolesSeeder::ROLE_BILLING);

    $tenant = $this->createTenant();
    $pro = Plan::where('code', 'pro')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $pro->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addMonth(),
    ]);

    $addon = PlanAddon::where('code', 'extra_user')->firstOrFail();

    SubscriptionAddon::create([
        'subscription_id' => $subscription->id,
        'plan_addon_id' => $addon->id,
        'quantity' => 3,
        'added_at' => now(),
    ]);

    Livewire::actingAs($billing, 'admin')
        ->test('admin.subscriptions.index')
        ->assertSet('mrr', 12500 + 3 * 1500)
        ->assertSet('activeCount', 1)
        ->assertSet('pastDueCount', 0);
});

test('billing:check-due sends a 7-day reminder to the manager once', function () {
    Mail::fake();

    $tenant = $this->createTenant();

    $tenant->run(function () {
        $manager = User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@manuelle.ci',
            'password' => 'mot-de-passe',
        ]);

        $manager->assignRole(TenantRolesSeeder::ROLE_MANAGER);
    });

    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_end' => now()->addDays(7),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    Mail::assertQueued(BillingReminderMail::class, function (BillingReminderMail $mail): bool {
        return $mail->hasTo('aya@manuelle.ci')
            && $mail->daysLeft === 7
            && $mail->amount === 5000;
    });

    expect($subscription->fresh()->reminders_sent)->toBe([7]);

    $this->artisan('billing:check-due')->assertSuccessful();

    Mail::assertQueued(BillingReminderMail::class, 1);
});

test('inviting a manager is blocked when the plan user limit is reached', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $tenant->run(function () {
        User::create([
            'name' => 'Gérant Existante',
            'email' => 'existante@manuelle.ci',
            'password' => 'mot-de-passe',
        ]);
    });

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('inviteManagerName', 'Aya Kouassi')
        ->set('inviteManagerEmail', 'aya@manuelle.ci')
        ->call('inviteManager')
        ->assertHasErrors(['inviteManagerEmail']);
});
