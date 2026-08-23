<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="description" content="Cakeman — le logiciel tout-en-un des pâtisseries : caisse, stocks, recettes, clients et facturation. Essai gratuit 14 jours, paiement par Mobile Money." />
<meta name="theme-color" content="#b45309" />

<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:type" content="website" />
<meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name') : config('app.name').' — gestion des pâtisseries' }}" />
<meta property="og:description" content="Caisse, stocks, recettes, clients et facturation réunis dans une seule application pour les artisans du goût." />
<meta property="og:image" content="{{ asset('img/og-cover.png') }}" />
<meta property="og:url" content="{{ url()->current() }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ config('app.name') }} — gestion des pâtisseries" />
<meta name="twitter:description" content="Caisse, stocks, recettes, clients et facturation réunis dans une seule application pour les artisans du goût." />
<meta name="twitter:image" content="{{ asset('img/og-cover.png') }}" />

<link rel="canonical" href="{{ url()->current() }}" />

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])