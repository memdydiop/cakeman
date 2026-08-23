<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Facture d'abonnement (Module 12, schéma central) — montant entier FCFA.
 *
 * @property int $id
 * @property string $number
 * @property string $tenant_id
 * @property int|null $subscription_id
 * @property int $amount
 * @property InvoiceStatus $status
 * @property string|null $payment_link_url
 * @property CarbonInterface|null $due_at
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $period_start
 * @property CarbonInterface|null $period_end
 * @property string|null $method
 * @property string|null $provider_ref
 * @property Tenant $tenant
 * @property Subscription|null $subscription
 */
class Invoice extends Model
{
    use CentralConnection;

    protected $fillable = [
        'number',
        'tenant_id',
        'subscription_id',
        'amount',
        'status',
        'payment_link_url',
        'due_at',
        'paid_at',
        'period_start',
        'period_end',
        'method',
        'provider_ref',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
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
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
