<?php

use App\Models\AdminUser;
use Database\Seeders\AdminRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    (new AdminRolesSeeder)->run();
});

test('changing the password requires the current password and updates it', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'securite@cakeman.test',
        'password' => 'ancien-mot-de-passe',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->set('currentPassword', 'ancien-mot-de-passe')
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('changePassword')
        ->assertHasNoErrors();

    expect(Hash::check('nouveau-mot-de-passe', $admin->fresh()->password))->toBeTrue();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'admin_user.password_changed',
        'admin_user_id' => $admin->id,
    ]);
});

test('a wrong current password is rejected', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'securite@cakeman.test',
        'password' => 'ancien-mot-de-passe',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->set('currentPassword', 'mauvais-mot-de-passe')
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('changePassword')
        ->assertHasErrors(['currentPassword']);

    expect(Hash::check('ancien-mot-de-passe', $admin->fresh()->password))->toBeTrue();
});

test('changing the password revokes the other sessions but keeps the current one', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'securite@cakeman.test',
        'password' => 'ancien-mot-de-passe',
    ]);

    $current = session()->getId();
    DB::table('sessions')->insert([
        'id' => $current,
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);
    DB::table('sessions')->insert([
        'id' => 'session-autre',
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest-mobile',
        'payload' => '{}',
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test('admin.security.index')
        ->set('currentPassword', 'ancien-mot-de-passe')
        ->set('password', 'nouveau-mot-de-passe')
        ->set('passwordConfirmation', 'nouveau-mot-de-passe')
        ->call('changePassword')
        ->assertHasNoErrors();

    expect(DB::table('sessions')->where('id', $current)->exists())->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'session-autre')->exists())->toBeFalse();
});
