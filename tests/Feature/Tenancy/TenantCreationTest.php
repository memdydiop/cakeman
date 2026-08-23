<?php

use App\Enums\TenantPermission;
use App\Enums\TenantStatus;
use App\Models\User;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('tenant creation provisions schema, migrations, roles and settings', function () {
    $tenant = $this->createTenant(['name' => 'Pâtisserie Test']);

    $schema = $tenant->database()->getName();

    $schemaExists = DB::connection('pgsql')
        ->selectOne('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?', [$schema]) !== null;

    expect($schemaExists)->toBeTrue();

    $tenant->run(function () use ($schema) {
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [$schema]);
        $tableNames = collect($tables)->pluck('table_name');

        expect($tableNames)->toContain('users', 'roles', 'permissions', 'tenant_settings');

        expect(DB::table('tenant_settings')->count())->toBe(1);
        expect(DB::table('tenant_settings')->first()->deposit_default_percent)->toBe(50);

        $roleNames = Role::pluck('name');
        expect($roleNames)->toContain(
            TenantRolesSeeder::ROLE_MANAGER,
            TenantRolesSeeder::ROLE_HEAD_CHEF,
            TenantRolesSeeder::ROLE_SALES,
            TenantRolesSeeder::ROLE_COURIER,
        )->toHaveCount(4);
    });
});

test('tenant domain is created', function () {
    $tenant = $this->createTenant(domain: 'douceur-abidjan.cakeman.test');

    expect($tenant->domains->pluck('domain'))->toContain('douceur-abidjan.cakeman.test');
});

test('tenant schema name derives from the tenant name slug', function () {
    $tenant = $this->createTenant(['name' => 'Maison des Délices']);

    expect($tenant->database()->getName())->toBe('tenant_maison-des-delices');
});

test('tenants with the same name get distinct schema names', function () {
    $first = $this->createTenant(['name' => 'Doublon Boutique']);
    $second = $this->createTenant(['name' => 'Doublon Boutique']);

    expect($first->database()->getName())->toBe('tenant_doublon-boutique')
        ->and($second->database()->getName())->toBe('tenant_doublon-boutique-2');
});

test('tenant starts with active status', function () {
    $tenant = $this->createTenant();

    expect($tenant->status)->toBe(TenantStatus::Active);
    expect($tenant->isAccessible())->toBeTrue();
});

test('suspended tenant is not accessible', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Suspended]);

    expect($tenant->status)->toBe(TenantStatus::Suspended);
    expect($tenant->isAccessible())->toBeFalse();
});

test('first tenant user can be created with manager role', function () {
    $tenant = $this->createTenant();

    $tenant->run(function () {
        $user = User::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya@exemple.ci',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole(TenantRolesSeeder::ROLE_MANAGER);

        expect($user->hasRole(TenantRolesSeeder::ROLE_MANAGER))->toBeTrue();
        expect($user->hasPermissionTo(TenantPermission::OrderCreate->value, 'web'))->toBeTrue();
    });
});
