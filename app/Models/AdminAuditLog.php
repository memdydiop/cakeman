<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Journal d'audit de la console plateforme (Module 12, plan-sprint0 § 5.7/5.8) :
 * chaque mutation sensible (tenant suspendu/activé, invitation, rôle,
 * désactivation) est tracée — suppression interdite si des entrées existent.
 *
 * @property int $id
 * @property int|null $admin_user_id
 * @property string|null $tenant_id
 * @property string $action
 * @property array<string, mixed> $properties
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 */
class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_user_id',
        'tenant_id',
        'action',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, array $properties = [], ?string $tenantId = null, ?int $adminUserId = null): self
    {
        return self::create([
            'admin_user_id' => $adminUserId ?? auth('admin')->id(),
            'tenant_id' => $tenantId,
            'action' => $action,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
