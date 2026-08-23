<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=fraunces:opsz,wght@9..144,600;9..144,700&display=swap">
    </head>
    <body class="bg-cream text-stone-900 antialiased">
        <nav class="sticky top-0 z-50 border-b border-amber-900/10 bg-cream/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-md bg-amber-600">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </span>
                    <span class="font-display text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-8 text-sm font-medium text-stone-600 md:flex">
                    <a href="#features" class="transition hover:text-stone-900">Fonctionnalités</a>
                    <a href="#pricing" class="transition hover:text-stone-900">Tarifs</a>
                    <a href="{{ route('faq') }}" class="transition hover:text-stone-900">FAQ</a>
                    <a href="{{ route('admin.login') }}" class="transition hover:text-stone-900">Console</a>
                </div>

                <a href="{{ route('register') }}" class="rounded-full bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">
                    Créer ma boutique
                </a>
            </div>
        </nav>

        <header class="mx-auto max-w-6xl px-4 pb-16 pt-20 sm:px-6 lg:pt-28">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="text-center lg:text-start">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800">
                        Plateforme tout-en-un pour boulangers &amp; pâtissiers
                    </span>

                    <h1 class="mt-6 font-display text-4xl font-semibold tracking-tight sm:text-6xl">
                        Le logiciel tout-en-un des pâtisseries.
                        <span class="block text-amber-600">Gagnez 2 heures par jour.</span>
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-lg text-stone-600 lg:mx-0">
                        Caisse, stocks, recettes, commandes et factures : Cakeman réunit tout ce qu'il faut pour gérer votre boutique, du fournil à la livraison.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                        <a href="{{ route('register') }}" class="rounded-full bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-700">
                            Créer ma boutique — gratuit
                        </a>
                        <a href="#features" class="rounded-full border border-amber-200 bg-white px-6 py-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-50">
                            Découvrir les fonctionnalités
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <img
                        src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=900&q=80&auto=format&fit=crop"
                        alt="Croissants artisanaux"
                        class="aspect-[4/3] w-full rounded-3xl object-cover shadow-xl shadow-amber-900/10"
                        loading="eager"
                    />

                    <div class="absolute -bottom-6 -start-4 hidden rounded-2xl border border-amber-200 bg-white px-5 py-4 shadow-lg sm:block">
                        <p class="text-xs font-medium text-stone-500">Nouvelle commande reçue</p>
                        <p class="mt-1 text-lg font-bold tracking-tight text-stone-900">Pièce montée — 45 000 FCFA</p>
                    </div>
                </div>
            </div>
        </header>

        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <div class="grid gap-4 rounded-3xl border border-amber-900/10 bg-white p-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 shrink-0 text-amber-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                    <p class="text-sm text-stone-600"><span class="font-semibold text-stone-900">2 minutes</span> pour créer votre boutique</p>
                </div>

                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 shrink-0 text-amber-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                    <p class="text-sm text-stone-600"><span class="font-semibold text-stone-900">Mobile Money</span> : Orange Money, MTN, Moov &amp; Wave</p>
                </div>

                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 shrink-0 text-amber-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    <p class="text-sm text-stone-600"><span class="font-semibold text-stone-900">Support WhatsApp</span> 7j/7</p>
                </div>

                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 shrink-0 text-amber-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                    </svg>
                    <p class="text-sm text-stone-600"><span class="font-semibold text-stone-900">Pensé pour la 3G/4G</span>, même en réseau lent</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-24 sm:px-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-1.5 border-b border-zinc-100 pb-3">
                        <span class="size-2 rounded-full bg-zinc-200"></span>
                        <span class="size-2 rounded-full bg-zinc-200"></span>
                        <span class="size-2 rounded-full bg-zinc-200"></span>
                    </div>
                    <p class="mt-4 text-sm font-semibold">Ventes du jour</p>
                    <p class="text-2xl font-bold tracking-tight">185 000 FCFA</p>
                    <div class="mt-4 flex h-16 items-end gap-1.5">
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 35%"></span>
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 55%"></span>
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 45%"></span>
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 75%"></span>
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 60%"></span>
                        <span class="w-full rounded-t bg-amber-500/70" style="height: 90%"></span>
                        <span class="w-full rounded-t bg-amber-600" style="height: 100%"></span>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold">Commandes du matin</p>
                    <div class="mt-4 space-y-2.5">
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2">
                            <span class="text-xs font-medium text-zinc-700">N°124 — Éclair chocolat</span>
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">Prête</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2">
                            <span class="text-xs font-medium text-zinc-700">N°125 — Pièce montée</span>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">En cours</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2">
                            <span class="text-xs font-medium text-zinc-700">N°126 — Gâteau 8 pers.</span>
                            <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700">Livrée</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold">Stock &amp; ingrédients</p>
                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="flex justify-between text-xs text-zinc-600">
                                <span>Farine T55</span><span>78 %</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-zinc-100">
                                <div class="h-1.5 w-[78%] rounded-full bg-amber-600"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-zinc-600">
                                <span>Beurre AOP</span><span>34 %</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-zinc-100">
                                <div class="h-1.5 w-[34%] rounded-full bg-amber-500"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-zinc-600">
                                <span>Chocolat 70 %</span><span>12 %</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-zinc-100">
                                <div class="h-1.5 w-[12%] rounded-full bg-red-500"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold">Facturation</p>
                    <div class="mt-4 rounded-lg border border-zinc-100 p-3">
                        <div class="flex items-center justify-between text-xs text-zinc-500">
                            <span>FACT-2026-0142</span>
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">Payée</span>
                        </div>
                        <p class="mt-2 text-xs font-medium text-zinc-700">Traiteur Cérémonie — 60 personnes</p>
                        <p class="mt-2 text-lg font-bold tracking-tight">890 000 FCFA</p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold">Planning de production</p>
                    <div class="mt-4 grid grid-cols-7 gap-1">
                        @foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $day)
                            <div class="rounded bg-zinc-50 py-1 text-center text-[11px] font-medium text-zinc-500">{{ $day }}</div>
                        @endforeach
                        <div class="col-span-2 rounded bg-amber-600 py-1 text-center text-[11px] text-white">Croissants</div>
                        <div class="col-span-2 rounded bg-zinc-200 py-1 text-center text-[11px] text-zinc-700">Macarons</div>
                        <div class="col-span-3 rounded bg-zinc-50 py-1 text-center text-[11px] text-zinc-500"></div>
                        <div class="col-span-4 rounded bg-amber-600 py-1 text-center text-[11px] text-white">Commandes mariage</div>
                        <div class="col-span-3 rounded bg-zinc-50 py-1 text-center text-[11px] text-zinc-500"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-900/10 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold">Clients &amp; fidélité</p>
                    <div class="mt-4 space-y-2.5">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-full bg-stone-700 text-[10px] font-semibold text-white">MD</span>
                            <span class="text-xs font-medium text-zinc-700">Marie Diallo</span>
                            <span class="ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">Or</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-full bg-zinc-300 text-[10px] font-semibold text-zinc-800">JK</span>
                            <span class="text-xs font-medium text-zinc-700">Jean Kouassi</span>
                            <span class="ml-auto rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700">Argent</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-full bg-amber-500 text-[10px] font-semibold text-white">AB</span>
                            <span class="text-xs font-medium text-zinc-700">Aïcha Bamba</span>
                            <span class="ml-auto rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700">Bronze</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-3xl px-4 pb-16 text-center sm:px-6">
            <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">
                Commandes sur WhatsApp, stocks sur papier, calculs à la main ?
            </h2>
            <p class="mt-4 text-lg text-stone-600">
                Entre les livraisons, les ingrédients et les clients, la gestion quotidienne dévore votre temps. Cakeman centralise tout au même endroit — et vous alerte avant les ruptures.
            </p>
        </section>

        <section id="features" class="mx-auto max-w-6xl scroll-mt-20 px-4 pb-24 sm:px-6">
            <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Tout ce qu'il faut pour gérer votre boutique</h2>
            <p class="mt-3 max-w-2xl text-lg text-zinc-600">Des fonctionnalités intégrées qui travaillent ensemble, sans module à installer.</p>

            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Ventes &amp; commandes</h3>
                    <p class="mt-2 text-sm text-zinc-600">Encaissements, commandes en ligne et caisse unifiée pour ne rien manquer.</p>
                </div>

                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Stock &amp; ingrédients</h3>
                    <p class="mt-2 text-sm text-zinc-600">Suivez vos matières premières et soyez alerté avant la rupture.</p>
                </div>

                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Recettes &amp; marges</h3>
                    <p class="mt-2 text-sm text-zinc-600">Centralisez vos recettes et calculez vos coûts de revient en temps réel.</p>
                </div>

                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Clients &amp; fidélité</h3>
                    <p class="mt-2 text-sm text-zinc-600">Fiches clients, points de fidélité et campagnes ciblées par SMS ou e-mail.</p>
                </div>

                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Facturation</h3>
                    <p class="mt-2 text-sm text-zinc-600">Devis, factures et relances en quelques clics, encaissement en ligne inclus.</p>
                </div>

                <div class="rounded-xl border border-amber-900/10 p-6 transition hover:border-amber-300 hover:shadow-sm">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-amber-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold">Analyse</h3>
                    <p class="mt-2 text-sm text-zinc-600">Tableau de bord temps réel : ventes, marges et tendances en un coup d'œil.</p>
                </div>
            </div>
        </section>

        <section id="pricing" class="mx-auto max-w-6xl scroll-mt-20 px-4 pb-24 sm:px-6">
            <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Des prix simples et justes</h2>
            <p class="mt-3 max-w-2xl text-lg text-zinc-600">Un seul prix, toutes les fonctionnalités incluses. Paiement par Mobile Money — essai gratuit 14 jours sans carte.</p>

            <div class="mt-8 flex flex-col items-center gap-3 has-checked:[&_.price-monthly]:hidden has-checked:[&_.price-yearly]:inline">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-zinc-600">Mensuel</span>
                    <label for="pricing-annual" class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full bg-zinc-200 transition">
                        <input id="pricing-annual" type="checkbox" class="peer sr-only">
                        <span class="absolute start-0.5 size-5 rounded-full bg-white shadow transition peer-checked:start-[1.375rem] peer-checked:bg-amber-600"></span>
                    </label>
                    <span class="text-sm font-medium text-zinc-600">Annuel <span class="font-semibold text-green-600">−20 %</span></span>
                </div>

            <div class="mt-7 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans as $plan)
                    <div class="{{ $plan->code === 'pro' ? 'relative border-2 border-amber-600' : 'border border-amber-900/10' }} flex flex-col rounded-xl p-6 {{ $plan->code === 'pro' ? '' : '' }}">
                        @if ($plan->code === 'pro')
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-amber-600 px-3 py-0.5 text-xs font-semibold text-white">Populaire</span>
                        @endif

                        <p class="text-sm font-semibold">{{ $plan->name }}</p>
                        <p class="mt-3">
                            @if ($plan->monthly_price === 0)
                                <span class="price-monthly text-4xl font-bold tracking-tight">0 FCFA</span>
                                <span class="price-yearly hidden text-4xl font-bold tracking-tight">0 FCFA</span>
                                <span class="text-sm text-zinc-500"> / toujours</span>
                            @else
                                <span class="price-monthly text-4xl font-bold tracking-tight">{{ number_format($plan->monthly_price, 0, ',', ' ') }} FCFA</span>
                                <span class="price-yearly hidden text-4xl font-bold tracking-tight">{{ number_format($plan->annual_price, 0, ',', ' ') }} FCFA</span>
                                <span class="price-monthly text-sm text-zinc-500"> / mois</span>
                                <span class="price-yearly hidden text-sm text-zinc-500"> / an</span>
                            @endif
                        </p>

                        <ul class="mt-6 flex-1 space-y-2.5 text-sm text-zinc-600">
                            @foreach ($plan->features as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        @if ($plan->monthly_price === 0)
                            <a href="{{ route('register', ['plan' => $plan->code]) }}" class="mt-6 rounded-full border border-amber-900/10 py-2 text-center text-sm font-semibold text-stone-800 transition hover:border-amber-300">Créer ma boutique</a>
                        @elseif ($plan->code === 'pro')
                            <a href="{{ route('register', ['plan' => $plan->code]) }}" class="mt-6 rounded-full bg-amber-600 py-2 text-center text-sm font-semibold text-white transition hover:bg-amber-700">Essai gratuit</a>
                        @else
                            <a href="{{ route('register', ['plan' => $plan->code]) }}" class="mt-6 rounded-full border border-amber-900/10 py-2 text-center text-sm font-semibold text-stone-800 transition hover:border-amber-300">Essai gratuit</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($addons as $addon)
                    <div class="rounded-xl border border-amber-900/10 p-6">
                        <h3 class="text-sm font-semibold">{{ $addon->name }}</h3>
                        <p class="mt-2 text-sm text-zinc-600">Disponible à la carte, prorata sur la période en cours.</p>
                        <p class="mt-3 text-sm font-semibold">Dès {{ number_format($addon->monthly_price, 0, ',', ' ') }} FCFA/{{ $addon->unit ?? 'mois' }}</p>
                    </div>
                @endforeach
            </div>
            </div>
        </section>

        <section id="demo" class="mx-auto max-w-6xl scroll-mt-20 px-4 pb-24 sm:px-6">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">
                        Une question ? Parlons-en.
                    </h2>
                    <p class="mt-4 text-lg text-zinc-600">
                        Démo personnalisée, migration de vos données, tarifs pour plusieurs boutiques :
                        notre équipe vous rappelle sous 24 h, sans engagement.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-zinc-600">
                        <li class="flex items-center gap-3">
                            <span class="flex size-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </span>
                            Accompagnement à la prise en main inclus
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex size-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </span>
                            Import de vos clients et stocks existants
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex size-6 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </span>
                            Formation de votre équipe sur site ou à distance
                        </li>
                    </ul>
                </div>

                <livewire:landing.lead-form />
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-24 sm:px-6">
            <div class="rounded-3xl bg-stone-800 px-6 py-16 text-center sm:px-12">
                <h2 class="font-display text-3xl font-semibold tracking-tight text-white sm:text-4xl">Prêt à simplifier votre boutique ?</h2>
                <p class="mx-auto mt-3 max-w-xl text-lg text-stone-300">Créez votre boutique en deux minutes. Gratuit, sans carte.</p>
                <a href="{{ route('register') }}" class="mt-8 inline-block rounded-full bg-amber-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-400">
                    Créer ma boutique gratuitement
                </a>
            </div>
        </section>

        <x-landing.footer />

        @fluxScripts
    </body>
</html>
