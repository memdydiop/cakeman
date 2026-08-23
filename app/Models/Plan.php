<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Plan d'abonnement (Module 12, schéma central).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $monthly_price
 * @property int $annual_price
 * @property array{users: int|null, orders_per_month: int|null, storage_gb: int|null} $limits
 * @property list<string> $features
 */
class Plan extends Model
{
    use CentralConnection;

    protected $fillable = [
        'code',
        'name',
        'monthly_price',
        'annual_price',
        'limits',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
