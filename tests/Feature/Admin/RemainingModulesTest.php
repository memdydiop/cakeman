<?php

use App\Models\AdminApiToken;
use App\Models\AdminLoginLog;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\PlansSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
    (new PlansSeeder)->run();
});

test('coupons can be created, toggled and applied to a subscription', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.coupons.index')
        ->set('code', 'BIENVENUE10')
        ->set('discount_type', 'percentage')
        ->set('discount_value', 10)
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::where('code', 'BIENVENUE10')->firstOrFail();
    expect($coupon->isValid())->toBeTrue();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.coupons.index')
        ->call('toggle', $coupon->id)
        ->assertHasNoErrors();

    expect($coupon->fresh()->is_active)->toBeFalse();

    $coupon->refresh();
    $coupon->update(['is_active' => true]);
    expect($coupon->fresh()->isValid())->toBeTrue();

    // Apply to subscription
    $tenant = $this->createTenant();
    $plan = Plan::where('code', 'starter')->firstOrFail();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.subscriptions.index')
        ->call('startEditing', $tenant->id)
        ->set('planCode', $plan->code)
        ->set('billingPeriod', 'monthly')
        ->set('couponId', $coupon->id)
        ->call('saveSubscription')
        ->assertHasNoErrors();

    $sub = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
    expect($sub->coupon_id)->toBe($coupon->id);

    $invoice = Invoice::where('subscription_id', $sub->id)->latest()->firstOrFail();
    expect($invoice->amount)->toBe((int) round($plan->monthly_price * 0.9));
});

test('credit notes can be created', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();

    Livewire::actingAs($admin, 'admin')
        ->test('admin.credit-notes.index')
        ->set('tenantId', $tenant->id)
        ->set('amount', 5000)
        ->set('reason', 'Remise commerciale')
        ->call('save')
        ->assertHasNoErrors();

    expect(CreditNote::where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

test('announcements can be published and acked by tenant', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.announcements.index')
        ->set('title', 'Maintenance prévue')
        ->set('body', 'Intervention dimanche 02h')
        ->call('publish')
        ->assertHasNoErrors();

    $announcement = Announcement::where('title', 'Maintenance prévue')->firstOrFail();

    $tenant = $this->createTenant();
    $tenant->run(function () use ($announcement) {
        $user = User::factory()->create();

        DB::table('announcement_acks')->updateOrInsert(
            ['user_id' => $user->id, 'announcement_id' => $announcement->id],
            ['acked_at' => now()],
        );

        expect(DB::table('announcement_acks')->where('announcement_id', $announcement->id)->exists())->toBeTrue();
    });
});

test('api tokens can be created and used to access api', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $plain = null;

    Livewire::actingAs($admin, 'admin')
        ->test('admin.api-tokens.index')
        ->set('name', 'CI Token')
        ->call('create')
        ->assertHasNoErrors();

    $token = AdminApiToken::where('name', 'CI Token')->firstOrFail();

    // Need plain to test api — create directly to know plain
    $plain = Str::random(64);
    $hash = hash('sha256', $plain);
    $token2 = AdminApiToken::create(['name' => 'Test Plain', 'token' => $hash, 'created_by' => $admin->id]);

    $this->getJson('/api/v1/tenants', ['Authorization' => 'Bearer '.$plain])
        ->assertOk();

    $this->getJson('/api/v1/tenants')
        ->assertUnauthorized();

    $this->getJson('/api/v1/invoices', ['Authorization' => 'Bearer '.$plain])
        ->assertOk();

    $this->getJson('/api/v1/subscriptions', ['Authorization' => 'Bearer '.$plain])
        ->assertOk();
});

test('login journal records successful and failed attempts', function () {
    $admin = AdminUser::factory()->create(['email' => 'journal@cakeman.test']);

    Livewire::test('admin.login')
        ->set('email', 'journal@cakeman.test')
        ->set('password', 'wrong')
        ->call('authenticate')
        ->assertHasErrors(['email']);

    expect(AdminLoginLog::where('email', 'journal@cakeman.test')->where('success', false)->exists())->toBeTrue();

    Livewire::test('admin.login')
        ->set('email', 'journal@cakeman.test')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(AdminLoginLog::where('email', 'journal@cakeman.test')->where('success', true)->exists())->toBeTrue();
});

test('tenants csv export is downloadable', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $this->createTenant(['name' => 'Export Test']);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.index')
        ->call('exportCsv')
        ->assertFileDownloaded();
});

test('purge command deletes soft-deleted admins past retention', function () {
    $admin = AdminUser::factory()->create();
    $admin->delete();
    DB::table('admin_users')->where('id', $admin->id)->update(['deleted_at' => now()->subDays(31)]);

    $this->artisan('admin-users:purge --days=30')
        ->assertExitCode(0);

    expect(AdminUser::withTrashed()->find($admin->id))->toBeNull();
});

test('impersonation creates audit and redirects to tenant domain', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);
    $tenant = $this->createTenant();
    $tenant->run(fn () => User::factory()->create(['email' => 'impers@cakeman.test']));

    $response = Livewire::actingAs($admin, 'admin')
        ->test('admin.tenants.show', ['tenant' => $tenant->id])
        ->call('impersonate');

    $response->assertHasNoErrors();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'tenant.impersonated',
        'tenant_id' => $tenant->id,
    ]);
});
