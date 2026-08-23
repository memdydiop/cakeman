<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $token
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_used_at
 */
class AdminApiToken extends Model
{
    protected $fillable = ['name', 'token', 'abilities', 'created_by', 'last_used_at', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
