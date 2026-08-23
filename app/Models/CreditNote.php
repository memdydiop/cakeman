<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int|null $invoice_id
 * @property int $amount
 * @property string $reason
 * @property int|null $created_by
 */
class CreditNote extends Model
{
    use CentralConnection;

    protected $fillable = ['tenant_id', 'invoice_id', 'amount', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
