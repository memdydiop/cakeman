<?php

namespace App\Enums;

/**
 * Permissions métier des tenants (schéma tenant) — identifiants stables,
 * centralisés en code (CdC § 2.3). La liste est extensible au fil des modules
 * (M1 → M11) : chaque action métier doit être couverte par une permission ici.
 */
enum TenantPermission: string
{
    case DashboardView = 'dashboard.view';

    case OrderCreate = 'order.create';
    case OrderView = 'order.view';
    case OrderCancel = 'order.cancel';

    case CustomerCreate = 'customer.create';
    case CustomerView = 'customer.view';

    case PaymentRecord = 'payment.record';

    case InventoryManage = 'inventory.manage';
    case InventoryView = 'inventory.view';

    case SettingsManage = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
