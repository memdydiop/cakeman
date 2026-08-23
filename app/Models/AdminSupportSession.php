<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Session support temporaire sur le schéma d'un tenant (CdC § 12) :
 * motif obligatoire, durée limitée, expiration automatique, lecture seule.
 *
 * @property int $id
 * @property int $admin_user_id
 * @property string $tenant_id
 * @property string $reason
 * @property int $duration_minutes
 * @property CarbonInterface $started_at
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $ended_at
 * @property AdminUser $adminUser
 * @property Tenant $tenant
 */
class AdminSupportSession extends Model
{
    use CentralConnection;

    protected $fillable = [
        'admin_user_id',
        'tenant_id',
        'reason',
        'duration_minutes',
        'started_at',
        'expires_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function isActive(): bool
    {
        return $this->ended_at === null && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        if ($this->ended_at !== null) {
            return 'ended';
        }

        return $this->expires_at->isFuture() ? 'active' : 'expired';
    }
}
