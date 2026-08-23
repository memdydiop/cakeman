<?php

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Mail\NewLeadMail;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

function settingsSuperAdmin(): AdminUser
{
    $admin = AdminUser::factory()->create(['password' => Hash::make('secret-password')]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    return $admin;
}

test('support cannot access the settings page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.settings.index')
        ->assertStatus(403);

    $this->actingAs($support, 'admin')->get(route('admin.settings.index'))->assertForbidden();
});

test('super admin sees the settings page with default values', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->assertOk()
        ->assertSee('Configuration')
        ->assertSet('general.trialDays', 14)
        ->assertSet('billing.graceDays', 7)
        ->assertSet('security.invitationExpiresHours', 48);
});

test('saving the general tab persists settings flushes the cache and audits', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('general.name', 'Cakeman CI')
        ->set('general.supportEmail', 'support@cakeman.ci')
        ->set('general.trialDays', 21)
        ->set('general.archiveDays', 45)
        ->set('general.maintenanceHours', 4)
        ->call('saveGeneral')
        ->assertHasNoErrors();

    expect(Setting::get('platform.name'))->toBe('Cakeman CI')
        ->and(Setting::get('platform.support_email'))->toBe('support@cakeman.ci')
        ->and(Setting::get('tenant.trial_days'))->toBe('21')
        ->and(Setting::get('tenant.archive_days'))->toBe('45')
        ->and(Setting::get('tenant.maintenance_hours'))->toBe('4');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'settings.updated',
    ]);
    expect(AdminAuditLog::where('action', 'settings.updated')->latest()->firstOrFail()->properties['section'])->toBe('général');
});

test('saving the billing tab persists grace days sandbox and webhook secret', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('billing.graceDays', 10)
        ->set('billing.sandboxMode', false)
        ->set('billing.webhookSecret', 'super-secret')
        ->call('saveBilling')
        ->assertHasNoErrors();

    expect(Setting::get('billing.grace_days'))->toBe('10')
        ->and(Setting::get('billing.sandbox_mode'))->toBe('0')
        ->and(Setting::get('billing.webhook_secret'))->toBe('super-secret');
});

test('saving the acquisition tab persists the lead notification email', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('acquisition.leadNotificationEmail', 'ventes@cakeman.ci')
        ->call('saveAcquisition')
        ->assertHasNoErrors();

    expect(Setting::get('acquisition.lead_notification_email'))->toBe('ventes@cakeman.ci');
});

test('saving the security tab persists invitation validity and mandatory 2fa', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('security.invitationExpiresHours', 24)
        ->set('security.twoFactorRequired', true)
        ->call('saveSecurity')
        ->assertHasNoErrors();

    expect(Setting::get('security.invitation_expires_hours'))->toBe('24')
        ->and(Setting::get('security.two_factor_required'))->toBe('1');
});

test('invalid values are rejected per tab', function () {
    $admin = settingsSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('general.trialDays', 0)
        ->call('saveGeneral')
        ->assertHasErrors('general.trialDays');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('billing.graceDays', 99)
        ->call('saveBilling')
        ->assertHasErrors('billing.graceDays');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('acquisition.leadNotificationEmail', 'pas-un-email')
        ->call('saveAcquisition')
        ->assertHasErrors('acquisition.leadNotificationEmail');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.settings.index')
        ->set('security.invitationExpiresHours', 0)
        ->call('saveSecurity')
        ->assertHasErrors('security.invitationExpiresHours');
});

test('setting get falls back to the default when unset', function () {
    expect(Setting::get('tenant.trial_days', 14))->toBe(14)
        ->and(Setting::get('inconnu', 'défaut'))->toBe('défaut');
});

test('settings stay readable inside an initialized tenant context', function () {
    $tenant = $this->createTenant();
    tenancy()->initialize($tenant);

    Setting::set('security.invitation_expires_hours', '72');

    expect(Setting::get('security.invitation_expires_hours', 48))->toBe('72');

    tenancy()->end();
});

test('onboarding uses the configured trial duration', function () {
    Setting::set('tenant.trial_days', 21);
    Mail::fake();

    Livewire::test('onboarding.register-tenant', ['plan' => 'pro'])
        ->set('businessName', 'Pâtisserie Réglée')
        ->set('subdomain', 'reglee')
        ->set('managerName', 'Aya Kouassi')
        ->set('email', 'aya@reglee.ci')
        ->call('register')
        ->assertHasNoErrors();

    $tenant = Tenant::where('name', 'Pâtisserie Réglée')->firstOrFail();
    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->trial_ends_at->isBetween(now()->addDays(20), now()->addDays(22)))->toBeTrue();
});

test('billing:check-due applies the configured grace days', function () {
    Setting::set('billing.grace_days', 10);
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'pro')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
        'current_period_start' => now()->subMonth(),
        'current_period_end' => now()->subDay(),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and((float) now()->diffInDays($subscription->grace_until))->toBeGreaterThan(9.9);
});

test('billing:check-due archives past the configured archive delay', function () {
    Setting::set('tenant.archive_days', 45);
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);
    $plan = Plan::where('code', 'pro')->firstOrFail();

    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'current_period_start' => now()->subMonths(2),
        'current_period_end' => now()->subMonth(),
        'grace_until' => now()->subDays(40),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    Subscription::where('tenant_id', $tenant->id)->update(['grace_until' => now()->subDays(50)]);

    $this->artisan('billing:check-due')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Cancelled);
});

test('mandatory 2fa blocks an admin login without totp configured', function () {
    Setting::set('security.two_factor_required', '1');
    $admin = AdminUser::factory()->create(['password' => Hash::make('secret-password')]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('admin login is throttled after five failed attempts', function () {
    $admin = AdminUser::factory()->create(['password' => Hash::make('secret-password')]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    foreach (range(1, 5) as $attempt) {
        Livewire::test('admin.login')
            ->set('email', $admin->email)
            ->set('password', 'mauvais-mot-de-passe')
            ->call('authenticate')
            ->assertHasErrors('email');
    }

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('a new lead notifies the configured acquisition email', function () {
    Mail::fake();
    Setting::set('acquisition.lead_notification_email', 'ventes@cakeman.ci');

    Livewire::test('landing.lead-form')
        ->set('name', 'Kouadio Paul')
        ->set('email', 'paul-notifie@exemple.ci')
        ->set('phone', '07 12 34 56 78')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    Mail::assertSent(NewLeadMail::class, fn (NewLeadMail $mail) => $mail->hasTo('ventes@cakeman.ci'));
});

test('no notification email is sent when none is configured', function () {
    Mail::fake();

    Livewire::test('landing.lead-form')
        ->set('name', 'Kouadio Paul')
        ->set('email', 'paul-sans-notif@exemple.ci')
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertNothingSent();
});

test('landing pages are under maintenance when enabled', function () {
    Setting::set('platform.maintenance_enabled', '1');
    Setting::set('platform.maintenance_message', 'Maintenance planifiée.');

    $this->get('/')->assertStatus(503)->assertSee('Maintenance planifiée');
    $this->get('/register')->assertStatus(503);
    $this->get('/faq')->assertStatus(503);

    Setting::set('platform.maintenance_enabled', '0');

    $this->get('/')->assertOk();
});

test('the webhook sandbox mode is driven by settings', function () {
    $tenant = $this->createTenant(['name' => 'Pâtisserie Sandbox']);
    $plan = Plan::where('code', 'starter')->firstOrFail();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'current_period_start' => now()->subMonth(),
        'current_period_end' => now(),
        'grace_until' => now()->addDays(7),
    ]);

    $invoice = Invoice::create([
        'number' => 'FACT-2026-00999',
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'amount' => 5000,
        'status' => InvoiceStatus::Pending,
        'due_at' => now()->addDays(7),
        'period_start' => now()->subMonth(),
        'period_end' => now(),
        'method' => 'wave',
        'provider_ref' => 'MM-FACT-SANDBOX-TEST',
    ]);

    Setting::set('billing.sandbox_mode', '0');
    Setting::set('billing.webhook_secret', '');

    $this->postJson(route('webhooks.mobile-money'), [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $invoice->provider_ref,
        'amount' => 5000,
    ])->assertStatus(401);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Pending);

    $secondInvoice = Invoice::create([
        'number' => 'FACT-2026-01000',
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'amount' => 5000,
        'status' => InvoiceStatus::Pending,
        'due_at' => now()->addDays(7),
        'period_start' => now()->subMonth(),
        'period_end' => now(),
        'method' => 'wave',
        'provider_ref' => 'MM-FACT-SANDBOX-TEST-2',
    ]);

    Setting::set('billing.sandbox_mode', '1');

    $this->postJson(route('webhooks.mobile-money'), [
        'provider' => 'wave',
        'event' => 'payment.succeeded',
        'provider_ref' => $secondInvoice->provider_ref,
        'amount' => 5000,
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($secondInvoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});
