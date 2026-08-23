<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\AdminAuditLog;
use App\Models\AdminWebhookLog;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Traitement des webhooks de paiement d'abonnement (CdC § 3, Module 12) :
 * idempotent via `provider_ref`, montant vérifié, paiement irréversible,
 * réactivation de l'abonnement et du tenant. Partagé par le endpoint public,
 * le rejeu manuel et la simulation de paiement.
 */
class SubscriptionPaymentProcessor
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(
        string $provider,
        string $event,
        string $providerRef,
        array $payload,
        bool $signatureValid,
        ?AdminWebhookLog $existingLog = null,
    ): AdminWebhookLog {
        $log = $existingLog ?? AdminWebhookLog::query()
            ->where('provider_ref', $providerRef)
            ->first();

        if ($existingLog === null && $log !== null) {
            return AdminWebhookLog::create([
                'provider' => $provider,
                'event' => $event,
                'provider_ref' => $providerRef,
                'payload' => $payload,
                'signature_valid' => $signatureValid,
                'status' => 'duplicate',
                'error' => 'Webhook dupliqué (provider_ref déjà traité)',
                'related_invoice_id' => $log->related_invoice_id,
                'processed_at' => now(),
            ]);
        }

        $log ??= AdminWebhookLog::create([
            'provider' => $provider,
            'event' => $event,
            'provider_ref' => $providerRef,
            'payload' => $payload,
            'signature_valid' => $signatureValid,
            'status' => 'received',
        ]);

        if (! $signatureValid) {
            return $this->mark($log, 'failed', 'Signature invalide');
        }

        if ($event !== 'payment.succeeded') {
            return $this->mark($log, 'ignored', 'Événement non géré : '.$event);
        }

        $invoice = Invoice::query()->where('provider_ref', $providerRef)->first();

        if ($invoice === null) {
            return $this->mark($log, 'failed', 'Aucune facture pour ce provider_ref');
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            return $this->mark($log, 'ignored', 'Facture déjà payée', $invoice);
        }

        $amount = (int) ($payload['amount'] ?? 0);

        if ($amount !== $invoice->amount) {
            return $this->mark($log, 'mismatch', 'Montant attendu '.$invoice->amount.' FCFA, reçu '.$amount, $invoice);
        }

        DB::transaction(function () use ($invoice, $provider): void {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
                'method' => $provider,
            ]);

            if ($subscription = $invoice->subscription) {
                $now = now();

                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                    'current_period_start' => $now,
                    'current_period_end' => $subscription->billing_period === BillingPeriod::Annual
                        ? $now->copy()->addYear()
                        : $now->copy()->addMonth(),
                    'grace_until' => null,
                ]);

                if ($invoice->tenant->status === TenantStatus::Suspended) {
                    $invoice->tenant->update(['status' => TenantStatus::Active]);
                }
            }

            AdminAuditLog::record(
                'invoice.paid',
                ['number' => $invoice->number, 'amount' => $invoice->amount, 'provider' => $provider],
                $invoice->tenant_id,
            );
        });

        return $this->mark($log, 'processed', null, $invoice);
    }

    /**
     * Consigne le statut final d'un log de webhook.
     */
    private function mark(AdminWebhookLog $log, string $status, ?string $error = null, ?Invoice $invoice = null): AdminWebhookLog
    {
        $log->update([
            'status' => $status,
            'error' => $error,
            'related_invoice_id' => $invoice?->id,
            'processed_at' => now(),
        ]);

        return $log;
    }
}
