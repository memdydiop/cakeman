<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['client_id', 'created_by', 'status', 'subtotal', 'tax', 'discount', 'total', 'deposit', 'balance', 'due_at', 'occasion', 'notes', 'custom_fields', 'delivery_type', 'delivery_address', 'payment_method', 'delivery_photo_path', 'delivery_signature', 'delivered_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'delivered_at' => 'datetime', 'custom_fields' => 'array'];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<TenantInvoice, $this> */
    public function tenantInvoice(): HasOne
    {
        return $this->hasOne(TenantInvoice::class, 'order_id');
    }
}
