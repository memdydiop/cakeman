<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property string $tenant_id
 * @property int $created_by
 * @property int|null $assigned_to
 * @property string $subject
 * @property string $body
 * @property string $status
 * @property string $priority
 * @property CarbonInterface|null $closed_at
 */
class SupportTicket extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'assigned_to',
        'subject',
        'body',
        'status',
        'priority',
        'closed_at',
    ];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<AdminUser, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /** @return BelongsTo<AdminUser, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    /** @return HasMany<SupportTicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public function isOpen(): bool
    {
        return $this->status !== 'closed';
    }
}
