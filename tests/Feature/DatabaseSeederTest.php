<?php

use App\Models\AdminUser;
use App\Models\User;
use Database\Seeders\AdminRolesSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

it('seeds the ghost admin with the Super-Admin role in central scope, idempotently', function (): void {
    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true])
        ->assertExitCode(0);

    $admin = AdminUser::where('email', 'admin@cakeman.ci')->firstOrFail();
    expect($admin->name)->toBe('Ghost Admin');

    $role = Role::where('name', AdminRolesSeeder::ROLE_SUPER_ADMIN)
        ->where('guard_name', 'admin')
        ->firstOrFail();
    expect($admin->roles()->find($role->id))->not->toBeNull();

    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true])
        ->assertExitCode(0);

    expect(AdminUser::where('email', 'admin@cakeman.ci')->count())->toBe(1);

    $this->assertSame(1, DB::table('model_has_roles')
        ->where('role_id', $role->id)
        ->where('model_id', $admin->id)
        ->count());
});

it('seeds the ghost user in tenancy scope, idempotently', function (): void {
    $tenant = $this->createTenant();

    tenancy()->initialize($tenant);

    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true])
        ->assertExitCode(0);

    expect(User::where('email', 'tenant@cakeman.ci')->first())->not->toBeNull();

    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true])
        ->assertExitCode(0);

    expect(User::where('email', 'tenant@cakeman.ci')->count())->toBe(1);
});
