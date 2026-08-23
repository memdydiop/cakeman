<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Abonnement d'un tenant (Module 12, schéma central) — un par boutique.
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $plan_id
 * @property BillingPeriod $billing_period
 * @property SubscriptionStatus $status
 * @property CarbonInterface|null $trial_ends_at
 * @property CarbonInterface|null $current_period_start
 * @property CarbonInterface|null $current_period_end
 * @property CarbonInterface|null $grace_until
 * @property list<int> $reminders_sent
 * @property CarbonInterface|null $cancelled_at
 * @property int|null $coupon_id
 * @property Coupon|null $coupon
 * @property Plan $plan
 * @property Tenant $tenant
 */
class Subscription extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_period',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'grace_until',
        'reminders_sent',
        'cancelled_at',
        'coupon_id',
    ];

    protected function casts(): array
    {
        return [
            'billing_period' => BillingPeriod::class,
            'status' => SubscriptionStatus::class,
            'reminders_sent' => 'array',
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_until' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
