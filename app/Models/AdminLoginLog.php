<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Journal des connexions console (Module 12 / CdC § 12) : chaque tentative
 * (réussie ou échouée) est consignée pour la supervision et l'audit.
 *
 * @property int $id
 * @property int|null $admin_user_id
 * @property string $email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $success
 * @property bool $two_factor
 * @property Carbon $created_at
 */
class AdminLoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'email',
        'ip_address',
        'user_agent',
        'success',
        'two_factor',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'two_factor' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
