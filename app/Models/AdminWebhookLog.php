<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Journal des webhooks de paiement des abonnements (CdC § 4.2, Module 12) :
 * payload brut, signature vérifiée, statut de traitement, rejeu manuel.
 *
 * @property int $id
 * @property string $provider
 * @property string $event
 * @property string|null $provider_ref
 * @property array<string, mixed> $payload
 * @property bool $signature_valid
 * @property string $status
 * @property int|null $related_invoice_id
 * @property string|null $error
 * @property CarbonInterface|null $processed_at
 * @property CarbonInterface|null $created_at
 * @property Invoice|null $invoice
 */
class AdminWebhookLog extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    protected $fillable = [
        'provider',
        'event',
        'provider_ref',
        'payload',
        'signature_valid',
        'status',
        'related_invoice_id',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'received' => 'Reçu',
            'processed' => 'Traité',
            'ignored' => 'Ignoré',
            'mismatch' => 'Écart de montant',
            'failed' => 'Échec',
            'duplicate' => 'Doublon',
            default => $this->status,
        };
    }
}
