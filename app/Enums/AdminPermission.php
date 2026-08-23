<?php

namespace App\Enums;

/**
 * Permissions de la console plateforme (schéma `public`, Module 12) —
 * identifiants stables centralisés en code (CdC § 2.3).
 */
enum AdminPermission: string
{
    case TenantView = 'admin.tenant.view';
    case TenantManage = 'admin.tenant.manage';

    case UserManage = 'admin.user.manage';

    case RoleManage = 'admin.role.manage';

    case BillingManage = 'admin.billing.manage';

    case SupportSession = 'admin.support.session';

    case MonitoringView = 'admin.monitoring.view';

    case AuditView = 'admin.audit.view';

    case WebhookReplay = 'admin.webhook.replay';

    case MaintenanceToggle = 'admin.maintenance.toggle';

    case LeadView = 'admin.lead.view';

    case SettingsManage = 'admin.settings.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }

    /**
     * Permissions groupées par domaine (Module 12) pour les formulaires.
     *
     * @return array<string, list<self>>
     */
    public static function groups(): array
    {
        return [
            'Boutiques' => [self::TenantView, self::TenantManage],
            'Équipe' => [self::UserManage, self::RoleManage],
            'Facturation' => [self::BillingManage],
            'Support' => [self::SupportSession],
            'Surveillance' => [self::MonitoringView],
            'Audit' => [self::AuditView],
            'Webhooks' => [self::WebhookReplay],
            'Maintenance' => [self::MaintenanceToggle],
            'Leads' => [self::LeadView],
            'Configuration' => [self::SettingsManage],
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::TenantView => 'Voir les boutiques',
            self::TenantManage => 'Gérer les boutiques',
            self::UserManage => 'Gérer l’équipe',
            self::RoleManage => 'Gérer les rôles',
            self::BillingManage => 'Gérer la facturation',
            self::SupportSession => 'Sessions support',
            self::MonitoringView => 'Voir la surveillance',
            self::AuditView => 'Voir l’audit',
            self::WebhookReplay => 'Rejouer les webhooks',
            self::MaintenanceToggle => 'Basculer la maintenance',
            self::LeadView => 'Voir les leads',
            self::SettingsManage => 'Gérer la configuration',
        };
    }
}
