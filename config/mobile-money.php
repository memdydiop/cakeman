<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile Money plateforme (CdC § 3, Module 12)
    |--------------------------------------------------------------------------
    | Agrégateur en mode "compte plateforme" (clés dédiées, hors tenant_settings).
    | Le lien de paiement est généré à chaque facture, le webhook confirme
    | (idempotent via provider_ref). En sandbox, aucun appel externe n'est fait.
    */

    'provider' => env('MOBILE_MONEY_PROVIDER', 'wave'),

    'sandbox' => env('MOBILE_MONEY_SANDBOX', true),

    'webhook_secret' => env('MOBILE_MONEY_WEBHOOK_SECRET', ''),

    'payment_link_url' => env('MOBILE_MONEY_PAYMENT_URL', 'https://pay.cakeman.test/mm'),
];
