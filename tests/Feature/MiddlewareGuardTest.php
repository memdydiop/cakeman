<?php

use App\Models\AdminUser;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
});

test('admin and web guards are distinct', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();
    $user = null;
    $tenant->run(function () use (&$user) {
        $user = User::factory()->create();
    });

    $this->actingAs($admin, 'admin');

    expect(auth('admin')->check())->toBeTrue();
    expect(auth('web')->check())->toBeFalse();
});

test('tenant users cannot access the admin console', function () {
    $tenant = $this->createTenant();
    $user = null;
    $tenant->run(function () use (&$user) {
        $user = User::factory()->create();
    });

    tenancy()->initialize($tenant);

    $this->actingAs($user)
        ->get(route('admin.tenants.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin users cannot access tenant pages', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole(AdminRolesSeeder::ROLE_SUPER_ADMIN);

    $tenant = $this->createTenant();

    $this->actingAs($admin, 'admin');

    $response = $this->tenantGet($tenant, route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('central login is not reachable on the central domain', function () {
    $this->get(route('login'))->assertNotFound();
});
