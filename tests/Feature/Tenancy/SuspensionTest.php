<?php

use App\Enums\TenantStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

test('suspended tenant cannot access tenant pages', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);

    $response = $this->tenantGet($tenant, route('dashboard'));

    $response->assertForbidden();
});

test('pending tenant cannot access tenant pages', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);

    $this->tenantGet($tenant, route('dashboard'))->assertForbidden();
});

test('suspended tenant cannot log in', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);

    $user = null;
    $tenant->run(function () use (&$user) {
        $user = User::create([
            'name' => 'Aya',
            'email' => 'aya@suspendue.ci',
            'password' => 'password',
        ]);
    });

    $response = $this->tenantPost($tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertForbidden();
    $this->assertGuest();
});

test('reactivated tenant regains access', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);

    $response = $this->tenantGet($tenant, route('dashboard'));
    $response->assertForbidden();

    $tenant->update(['status' => TenantStatus::Active]);

    $response = $this->tenantGet($tenant, route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('an inactive user cannot log in', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $user = null;
    $tenant->run(function () use (&$user) {
        $user = User::create([
            'name' => 'Aya',
            'email' => 'aya@desactivee.ci',
            'password' => 'password',
            'is_active' => false,
        ]);
    });

    $response = $this->tenantPost($tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an active user can log in', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $user = null;
    $tenant->run(function () use (&$user) {
        $user = User::create([
            'name' => 'Aya',
            'email' => 'aya@active.ci',
            'password' => 'password',
            'is_active' => true,
        ]);
    });

    $response = $this->tenantPost($tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('a passkey from an inactive user is rejected at login', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Active]);

    $request = Request::create('/passkeys/login', 'POST');

    $user = new User(['is_active' => false]);
    $passkey = new Passkey;

    $passkey->setRelation('user', $user);

    expect(Passkeys::allowsLogin($request, $passkey))->toBeFalse();

    $user->is_active = true;

    expect(Passkeys::allowsLogin($request, $passkey))->toBeTrue();
});
