@extends('layouts.landing', ['title' => 'Questions fréquentes'])

@section('content')
    <div class="space-y-4">
        @foreach ([
            'Combien coûte Cakeman ?' => 'Le plan Gratuit est disponible sans limite de durée pour une boutique. Les plans payants démarrent à 5 000 FCFA/mois et incluent un essai gratuit de 14 jours, sans carte bancaire. Le paiement se fait par Mobile Money (Wave, Orange Money, MTN).',
            'Comment créer ma boutique ?' => 'Renseignez le formulaire d\'inscription : votre nom de boutique, le sous-domaine souhaité et l\'e-mail du gérant. Vous recevez un lien d\'activation valable 48 heures, puis c\'est parti en moins de deux minutes.',
            'Est-ce que je peux changer de formule plus tard ?' => 'Oui, à tout moment depuis la console. Le passage à un plan supérieur est immédiat avec un prorata sur la période en cours ; le passage à un plan inférieur s\'applique à la prochaine échéance.',
            'Mes données sont-elles en sécurité ?' => 'Chaque boutique dispose de sa propre base de données, isolée des autres. Les connexions sont chiffrées (HTTPS) et l\'accès à la console d\'administration est restreint à l\'équipe Cakeman, avec journalisation de chaque action.',
            'Puis-je exporter mes données ?' => 'Oui. Vous pouvez exporter vos données à tout moment (export RGPD), et les supprimer définitivement si vous fermez votre boutique.',
            'Proposez-vous un accompagnement ?' => 'Oui : démonstration personnalisée, import de vos données existantes et formation de votre équipe. Laissez-nous vos coordonnées via le formulaire de démonstration, on vous rappelle sous 24 h.',
        ] as $question => $answer)
            <details class="group rounded-xl border border-amber-900/10 bg-white p-5">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold">
                    {{ $question }}
                    <span class="text-amber-600 transition group-open:rotate-45">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </span>
                </summary>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $answer }}</p>
            </details>
        @endforeach
    </div>
@endsection