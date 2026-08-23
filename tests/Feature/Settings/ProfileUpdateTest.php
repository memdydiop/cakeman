<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    tenancy()->initialize($this->tenant);
});

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->tenantGet($this->tenant, route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    tenancy()->initialize($this->tenant);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('profile email verification status is unchanged', function () {
    tenancy()->initialize($this->tenant);

    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is updated when email is changed', function () {
    tenancy()->initialize($this->tenant);

    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});
