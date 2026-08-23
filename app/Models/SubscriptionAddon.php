<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class SubscriptionAddon extends Model
{
    use CentralConnection;

    protected $fillable = [
        'subscription_id',
        'plan_addon_id',
        'quantity',
        'added_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<PlanAddon, $this>
     */
    public function planAddon(): BelongsTo
    {
        return $this->belongsTo(PlanAddon::class);
    }
}
