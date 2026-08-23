<?php

namespace Database\Seeders;

use App\Enums\TenantPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC tenant (schéma tenant, CdC § 2.3) : 4 rôles racines + permissions enum
 * métier. Exécuté à la création du schéma (pipeline TenantCreated).
 */
class TenantRolesSeeder extends Seeder
{
    public const ROLE_MANAGER = 'Gérant';

    public const ROLE_HEAD_CHEF = 'Chef Pâtissier';

    public const ROLE_SALES = 'Vendeur';

    public const ROLE_COURIER = 'Livreur';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (TenantPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $manager = Role::findOrCreate(self::ROLE_MANAGER, 'web');
        $manager->syncPermissions(TenantPermission::values());

        $headChef = Role::findOrCreate(self::ROLE_HEAD_CHEF, 'web');
        $headChef->syncPermissions([
            TenantPermission::DashboardView->value,
            TenantPermission::InventoryManage->value,
            TenantPermission::InventoryView->value,
        ]);

        $sales = Role::findOrCreate(self::ROLE_SALES, 'web');
        $sales->syncPermissions([
            TenantPermission::DashboardView->value,
            TenantPermission::OrderCreate->value,
            TenantPermission::OrderView->value,
            TenantPermission::CustomerCreate->value,
            TenantPermission::CustomerView->value,
            TenantPermission::PaymentRecord->value,
            TenantPermission::InventoryView->value,
        ]);

        $courier = Role::findOrCreate(self::ROLE_COURIER, 'web');
        $courier->syncPermissions([
            TenantPermission::OrderView->value,
        ]);
    }
}
