@extends('layouts.landing', ['title' => "Conditions d'utilisation"])

@section('content')
    <p class="text-sm text-zinc-600 leading-relaxed" class="lead">Dernière mise à jour : {{ date('F Y', strtotime('now')) }}</p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">1. Objet</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Les présentes conditions régissent l'utilisation de la plateforme {{ config('app.name') }},
        logiciel de gestion destiné aux artisans du goût (ventes, stock, recettes, clients, facturation).
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">2. Création de compte</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        L'inscription se fait avec un sous-domaine dédié et l'e-mail du gérant, qui reçoit un lien
        d'activation valable 48 heures. Vous êtes responsable de la confidentialité de vos accès et
        de l'activité de votre boutique.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">3. Abonnement et paiement</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Les plans payants sont facturés par Mobile Money, au mois ou à l'année. L'essai gratuit de
        14 jours ne requiert aucun paiement. À défaut de règlement à l'échéance, une période de grâce
        de 7 jours s'applique avant suspension de la boutique, puis archivage après 30 jours de suspension.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">4. Utilisation loyale</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Il est interdit d'utiliser la plateforme à des fins illicites, de tenter de porter atteinte à
        son fonctionnement ou d'accéder à des données d'autres boutiques. {{ config('app.name') }} se
        réserve le droit de suspendre tout compte en cas de manquement.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">5. Responsabilité</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        {{ config('app.name') }} s'engage à mettre en œuvre les moyens raisonnables pour assurer la
        disponibilité et l'intégrité du service, sans garantie d'absence totale d'interruption. Les
        données de la boutique restent la responsabilité du gérant, qui dispose d'outils d'export.
    </p>

    <h2 class="text-xl font-semibold tracking-tight pt-4">6. Droit applicable</h2>
    <p class="text-sm text-zinc-600 leading-relaxed">
        Les présentes conditions sont soumises au droit ivoirien. Tout litige relève des juridictions
        compétentes d'Abidjan, après une tentative de résolution amiable.
    </p>
@endsection