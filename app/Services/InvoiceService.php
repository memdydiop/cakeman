<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\InvoiceStatus;
use App\Models\AdminAuditLog;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\CarbonInterface;

/**
 * Génération des factures d'abonnement (CdC § 3, Module 12) : une facture
 * pending/overdue par période, montant FCFA entier, lien de paiement Mobile
 * Money + `provider_ref` unique (idempotence webhook).
 */
class InvoiceService
{
    public function __construct(private readonly MobileMoneyPayment $mobileMoney) {}

    /**
     * Crée la facture de la période si aucune n'est en attente : renvoie la
     * facture, ou null pour un plan gratuit (aucun paiement attendu).
     */
    public function createForSubscription(Subscription $subscription, CarbonInterface $periodStart, CarbonInterface $periodEnd): ?Invoice
    {
        $pendingExists = Invoice::query()
            ->where('subscription_id', $subscription->id)
            ->whereIn('status', [InvoiceStatus::Pending->value, InvoiceStatus::Overdue->value])
            ->exists();

        if ($pendingExists) {
            return null;
        }

        $amount = $subscription->billing_period === BillingPeriod::Annual
            ? $subscription->plan->annual_price
            : $subscription->plan->monthly_price;

        if ($amount <= 0) {
            return null;
        }

        $invoice = Invoice::create([
            'number' => 'TMP-'.$subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => InvoiceStatus::Pending,
            'due_at' => now()->addDays(7),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $invoice->update([
            'number' => 'FACT-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
        ]);

        $link = $this->mobileMoney->createPaymentLink($invoice);

        $invoice->update([
            'payment_link_url' => $link['url'],
            'provider_ref' => $link['provider_ref'],
            'method' => $this->mobileMoney->provider(),
        ]);

        AdminAuditLog::record(
            'invoice.created',
            ['number' => $invoice->number, 'amount' => $invoice->amount],
            $invoice->tenant_id,
        );

        return $invoice;
    }

    /**
     * Passe les factures en attente de la période en retard (impayé).
     */
    public function markOverdue(Subscription $subscription): void
    {
        Invoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', InvoiceStatus::Pending->value)
            ->update(['status' => InvoiceStatus::Overdue->value]);
    }
}
