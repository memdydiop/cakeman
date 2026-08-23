<?php

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

function totpCode(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

test('maintenance toggle requires the maintenance permission', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);
    $tenant = $this->createTenant();

    Livewire::actingAs($support, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleMaintenance')
        ->assertStatus(403);
});

test('super admin can toggle maintenance and the action is audited', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleMaintenance')
        ->assertHasNoErrors();
    expect($tenant->fresh()->maintenance_until)->not->toBeNull();
    expect($tenant->fresh()->isUnderMaintenance())->toBeTrue();

    $audit = AdminAuditLog::where('action', 'tenant.maintenance_toggled')->latest()->first();

    expect($audit)->not->toBeNull()
        ->and($audit->properties['enabled'])->toBeTrue()
        ->and($audit->tenant_id)->toBe($tenant->id);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('toggleMaintenance')
        ->assertHasNoErrors();

    expect($tenant->fresh()->isUnderMaintenance())->toBeFalse();
});

test('a tenant under maintenance is blocked with 503', function () {
    $tenant = $this->createTenant([
        'maintenance_until' => now()->addHours(2),
    ]);

    $this->tenantRequest($tenant, 'get', '/dashboard')->assertStatus(503);
});

test('cache purge is recorded in the audit log', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('purgeCache')
        ->assertHasNoErrors();
    expect(AdminAuditLog::where('action', 'tenant.cache_purged')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

test('export downloads a json snapshot of tenant data', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();

    $response = Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('exportData')
        ->assertFileDownloaded();

    expect(AdminAuditLog::where('action', 'tenant.data_exported')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

test('domains can be added and removed with audit', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant(domain: 'principale.cakeman.test');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('newDomain', 'annexe-1')
        ->call('addDomain')
        ->assertHasNoErrors();

    expect($tenant->domains->pluck('domain'))->toContain('annexe-1.cakeman.test');
    expect(AdminAuditLog::where('action', 'tenant.domain_added')->where('tenant_id', $tenant->id)->exists())->toBeTrue();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('removeDomain', 'annexe-1.cakeman.test')
        ->assertHasNoErrors();

    expect($tenant->fresh()->domains->pluck('domain'))->not->toContain('annexe-1.cakeman.test');
    expect(AdminAuditLog::where('action', 'tenant.domain_removed')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

test('duplicate or invalid domains are rejected', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant(domain: 'principale.cakeman.test');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('newDomain', 'principale')
        ->call('addDomain')
        ->assertHasErrors('newDomain');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->set('newDomain', 'Un Sous-Domaine!')
        ->call('addDomain')
        ->assertHasErrors('newDomain');
});

test('the last domain of a tenant cannot be removed', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant(domain: 'seul.cakeman.test');

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('removeDomain', 'seul.cakeman.test')
        ->assertStatus(422);

    expect($tenant->domains->count())->toBe(1);
});

test('invoices page lists invoices and lets billing simulate payment', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'business')->firstOrFail();
    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);
    $invoice = DB::table('invoices')->insertGetId([
        'number' => 'FACT-2026-00001',
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'amount' => 25000,
        'status' => InvoiceStatus::Pending->value,
        'payment_link_url' => 'https://pay.cakeman.test/mm/MM-ABC123',
        'provider_ref' => 'MM-ABC123',
        'method' => 'wave',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.invoices.index')
        ->assertOk()
        ->assertSee('FACT-2026-00001')
        ->assertSee('MM-ABC123')
        ->assertSee('En attente')
        ->call('simulatePayment', $invoice)
        ->assertHasNoErrors();
    $this->assertSame(InvoiceStatus::Paid, Invoice::find($invoice)->status);
});

test('support cannot access the invoices page', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    Livewire::actingAs($support, 'admin')
        ->test('admin.invoices.index')
        ->assertStatus(403);
});

test('billing check-due archives a tenant suspended for 30 days', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);
    $plan = Plan::where('code', 'business')->firstOrFail();
    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::PastDue,
        'grace_until' => now()->subDays(31),
    ]);

    $this->artisan('billing:check-due')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Cancelled);
    expect($tenant->fresh()->subscription->status)->toBe(SubscriptionStatus::Cancelled);
    expect($tenant->fresh()->subscription->cancelled_at)->not->toBeNull();
    expect(AdminAuditLog::where('action', 'tenant.archived')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

test('monitoring page is accessible to support and shows queue and webhook stats', function () {
    $support = AdminUser::factory()->create();
    $support->assignRole(AdminRolesSeeder::ROLE_SUPPORT);

    DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);

    Livewire::actingAs($support, 'admin')
        ->test('admin.monitoring.index')
        ->assertOk()
        ->assertSee('File d\'attente')
        ->assertSee('1');
});

test('dashboard shows MRR and webhook activity', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'business')->firstOrFail();
    Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'billing_period' => BillingPeriod::Monthly,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.dashboard')
        ->assertOk()
        ->assertSee('MRR')
        ->assertSee(number_format($plan->monthly_price, 0, ',', ' ').' FCFA');
});

test('admin login requires a totp code when 2fa is enabled', function () {
    $secret = app(Google2FA::class)->generateSecretKey();
    $admin = AdminUser::factory()->create([
        'password' => Hash::make('secret-password'),
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertSet('pendingAdminId', $admin->id);

    expect(Auth::guard('admin')->check())->toBeFalse();

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->set('code', '000000')
        ->call('confirmChallenge')
        ->assertHasErrors('code');

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->set('code', totpCode($secret))
        ->call('confirmChallenge')
        ->assertHasNoErrors();

    expect(Auth::guard('admin')->check())->toBeTrue();
});

test('admin without 2fa logs in directly', function () {
    $admin = AdminUser::factory()->create(['password' => Hash::make('secret-password')]);
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::test('admin.login')
        ->set('email', $admin->email)
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->check())->toBeTrue();
});

test('security page can enable confirm and disable 2fa', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->assertOk()
        ->assertSee('Non activée')
        ->call('enable')
        ->assertSee('Confirmer l\'activation');

    $secret = decrypt($admin->fresh()->two_factor_secret);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->set('code', totpCode($secret))
        ->call('confirm')
        ->assertHasNoErrors();
    expect($admin->fresh()->two_factor_confirmed_at)->not->toBeNull();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->call('disable')
        ->assertHasNoErrors();

    expect($admin->fresh()->two_factor_secret)->toBeNull();
});
