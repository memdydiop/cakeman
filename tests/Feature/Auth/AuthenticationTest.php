<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    tenancy()->initialize($this->tenant);
    $this->domain = $this->tenant->domains->first()->domain;
});

test('login screen can be rendered', function () {
    $response = $this->tenantGet($this->tenant, route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->tenantPost($this->tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->tenantPost($this->tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->tenantPost($this->tenant, route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->tenantPost($this->tenant, route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
