<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TenantCoupon extends Model
{
    protected $table = 'tenant_coupons';

    protected $fillable = ['code', 'type', 'value', 'valid_from', 'valid_until', 'max_uses', 'used_count', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'valid_from' => 'datetime', 'valid_until' => 'datetime'];
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->valid_from && Carbon::parse($this->valid_from)->isFuture()) {
            return false;
        }
        if ($this->valid_until && Carbon::parse($this->valid_until)->isPast()) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
