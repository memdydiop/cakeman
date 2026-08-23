<?php

namespace Database\Seeders;

use App\Enums\AdminPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC plateforme (schéma `public`, CdC § 2.3) : rôles racines Super-Admin,
 * Support, Billing + permissions enum `admin.*`. Exécuté dans le scope central.
 */
class AdminRolesSeeder extends Seeder
{
    public const ROLE_SUPER_ADMIN = 'Super-Admin';

    public const ROLE_SUPPORT = 'Support';

    public const ROLE_BILLING = 'Billing';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        // Sans events Eloquent (WithoutModelEvents sur le seeder racine), le
        // cache du registre ne se rafraîchit pas après les INSERTs.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate(self::ROLE_SUPER_ADMIN, 'admin');
        $superAdmin->syncPermissions(AdminPermission::values());

        $support = Role::findOrCreate(self::ROLE_SUPPORT, 'admin');
        $support->syncPermissions([
            AdminPermission::TenantView->value,
            AdminPermission::SupportSession->value,
            AdminPermission::MonitoringView->value,
            AdminPermission::AuditView->value,
            AdminPermission::WebhookReplay->value,
        ]);

        $billing = Role::findOrCreate(self::ROLE_BILLING, 'admin');
        $billing->syncPermissions([
            AdminPermission::TenantView->value,
            AdminPermission::BillingManage->value,
            AdminPermission::LeadView->value,
        ]);
    }
}
