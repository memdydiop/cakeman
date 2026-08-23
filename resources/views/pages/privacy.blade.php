@extends('layouts.landing', ['title' => 'Confidentialité et RGPD'])

@section('content')
    <p class="text-sm text-zinc-600 leading-relaxed" class="lead">Dernière mise à jour : {{ date('F Y', strtotime('now')) }}</p>

    <h2 class="text-xl font-semibold tracking-tight pt-4" id="rgpd">1. Données collectées</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        {{ config('app.name') }} collecte uniquement les données nécessaires au fonctionnement du service :
        identité et coordonnées des gérants (nom, e-mail, téléphone), informations de la boutique
        (clients, stocks, recettes, commandes, factures), et données techniques de connexion (adresse IP,
        journal d'audit). Les moyens de paiement sont traités par notre agrégateur Mobile Money ; nous ne
        stockons jamais vos numéros de carte.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">2. Utilisation et base légale</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Vos données sont utilisées pour fournir le service (exécution du contrat), vous accompagner
        (intérêt légitime) et respecter nos obligations comptables et fiscales (obligation légale).
        Elles ne sont jamais revendues à des tiers.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">3. Hébergement et sécurité</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Chaque boutique dispose d'une base de données isolée. Les échanges sont chiffrés en transit,
        les accès administratifs sont limités et journalisés (traçabilité de chaque action), et les
        mots de passe sont hachés avec des algorithmes éprouvés.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">4. Durée de conservation</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Les données d'une boutique active sont conservées le temps du contrat. Après annulation, la
        boutique est archivée 30 jours puis supprimée définitivement, sauf obligation légale de
        conservation (facturation).
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">5. Vos droits</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Conformément au RGPD, vous pouvez à tout moment : accéder à vos données, demander leur
        correction, leur effacement, leur portabilité (export JSON), ou vous opposer à leur traitement.
        Contactez-nous à support@cakeman.ci — réponse sous 72 h.
    </p>
@endsection