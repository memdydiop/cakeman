<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Invitation d'un utilisateur plateforme (Module 12) : lien one-time,
 * expiration 48 h, rôle proposé (CdC § 12, plan-sprint0 § 5.8).
 *
 * @property int $id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AdminUserInvitation extends Model
{
    public const EXPIRES_IN_HOURS = 48;

    protected $fillable = [
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
