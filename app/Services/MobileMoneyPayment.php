<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;

/**
 * Lien de paiement Mobile Money (CdC § 3, Module 12) : mode "compte
 * plateforme", agrégateur Wave/OM/MTN/Moov. En sandbox, le lien est
 * généré localement sans appel externe.
 */
class MobileMoneyPayment
{
    /**
     * Génère un lien de paiement pour une facture : renvoie l'URL et la
     * référence fournisseur unique (idempotence webhook).
     *
     * @return array{url: string, provider_ref: string}
     */
    public function createPaymentLink(Invoice $invoice): array
    {
        $providerRef = 'MM-'.$invoice->number.'-'.Str::upper(Str::random(10));

        $url = rtrim((string) config('mobile-money.payment_link_url'), '/').'/'.$providerRef;

        return [
            'url' => $url,
            'provider_ref' => $providerRef,
        ];
    }

    public function provider(): string
    {
        return (string) config('mobile-money.provider', 'wave');
    }
}
