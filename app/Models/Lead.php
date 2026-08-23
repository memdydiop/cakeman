<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Prospect de la landing (Module 13, schéma central) — dédoublonnage e-mail
 * et téléphone, cycle `new → contacted → converted` (boutique créée).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $shop_name
 * @property string|null $plan_code
 * @property string $source
 * @property LeadStatus $status
 * @property string|null $notes
 * @property string|null $converted_tenant_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonInterface $created_at
 * @property Tenant|null $convertedTenant
 */
class Lead extends Model
{
    use CentralConnection;

    protected $table = 'landing_leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'shop_name',
        'plan_code',
        'source',
        'status',
        'notes',
        'converted_tenant_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
        ];
    }

    /**
     * Boutique créée à partir de ce lead (conversion).
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function convertedTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'converted_tenant_id');
    }

    /**
     * Normalisation téléphone Côte d'Ivoire (CdC § 13, dédoublonnage +225) :
     * « 07 12 34 56 78 » → « 225712345678 ».
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '225')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '225'.$digits;
    }
}
