<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'sku', 'price', 'vat_rate', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'vat_rate' => 'decimal:2'];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasOne<Recipe, $this> */
    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }
}
