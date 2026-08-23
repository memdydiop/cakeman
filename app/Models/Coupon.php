<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $discount_type
 * @property int $discount_value
 * @property bool $is_active
 * @property int|null $max_uses
 * @property int $used_count
 * @property Carbon|null $expires_at
 */
class Coupon extends Model
{
    protected $fillable = ['code', 'discount_type', 'discount_value', 'is_active', 'max_uses', 'used_count', 'expires_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
