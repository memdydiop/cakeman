<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Invitation d'un gérant de boutique (Console admin, plan-sprint0 § 5.5) :
 * lien one-time côté tenant, expiration 48 h. À l'acceptation, le compte
 * Gérant est créé et la boutique passe au statut actif.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantUserInvitation extends Model
{
    public const EXPIRES_IN_HOURS = 48;

    protected $fillable = [
        'name',
        'email',
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
