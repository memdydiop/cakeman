<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Add-on à la carte (Module 12, schéma central).
 */
class PlanAddon extends Model
{
    use CentralConnection;

    protected $fillable = [
        'code',
        'name',
        'monthly_price',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
