<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = ['name', 'unit', 'stock_quantity', 'unit_cost', 'low_stock_threshold', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'stock_quantity' => 'decimal:2', 'low_stock_threshold' => 'decimal:2'];
    }

    /** @return HasMany<RecipeIngredient, $this> */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function isLowStock(): bool
    {
        return $this->low_stock_threshold !== null && $this->stock_quantity <= $this->low_stock_threshold;
    }
}
