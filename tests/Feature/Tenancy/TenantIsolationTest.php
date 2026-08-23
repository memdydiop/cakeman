<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;

test('users are isolated between tenants', function () {
    $tenantA = $this->createTenant(['name' => 'Boutique A']);
    $tenantB = $this->createTenant(['name' => 'Boutique B']);

    $tenantA->run(function () {
        User::create([
            'name' => 'User A',
            'email' => 'a@exemple.ci',
            'password' => 'password',
        ]);
    });

    $tenantB->run(function () {
        expect(User::where('email', 'a@exemple.ci')->exists())->toBeFalse();
        expect(User::count())->toBe(0);
    });

    $tenantA->run(function () {
        expect(User::where('email', 'a@exemple.ci')->exists())->toBeTrue();
    });
});

test('central context is restored after ending tenancy', function () {
    $tenant = $this->createTenant();

    tenancy()->initialize($tenant);
    expect(tenant()->id)->toBe($tenant->id);

    tenancy()->end();

    expect(tenancy()->initialized)->toBeFalse();
    expect(Tenant::count())->toBeGreaterThanOrEqual(1);
});

test('suspended tenant schema does not leak into other tenants', function () {
    $tenantA = $this->createTenant(['name' => 'Boutique A']);
    $tenantB = $this->createTenant(['name' => 'Boutique B', 'status' => TenantStatus::Suspended]);

    $tenantA->run(function () {
        User::create([
            'name' => 'User A',
            'email' => 'a@exemple.ci',
            'password' => 'password',
        ]);
    });

    $tenantB->run(function () {
        expect(User::count())->toBe(0);
    });

    $tenantA->run(function () {
        expect(User::where('email', 'a@exemple.ci')->exists())->toBeTrue();
    });
});
