<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $tenant_id
 * @property int $attempt
 * @property string $status
 * @property string|null $last_error
 * @property CarbonInterface $attempted_at
 * @property CarbonInterface|null $next_retry_at
 */
class SubscriptionDunningAttempt extends Model
{
    use CentralConnection;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'attempt',
        'status',
        'last_error',
        'attempted_at',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
