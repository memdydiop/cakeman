<footer class="border-t border-amber-900/10">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-md bg-amber-600">
                    <x-app-logo-icon class="size-5 fill-current text-white" />
                </span>
                <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <p class="mt-4 text-sm text-zinc-500">Ventes, stock, recettes, clients et facturation dans une seule application pour les artisans du goût.</p>
        </div>

        <div>
            <h3 class="text-sm font-semibold">Produit</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                <li><a href="{{ route('home') }}#features" class="transition hover:text-stone-900">Fonctionnalités</a></li>
                <li><a href="{{ route('home') }}#pricing" class="transition hover:text-stone-900">Tarifs</a></li>
                <li><a href="{{ route('register') }}" class="transition hover:text-stone-900">Créer ma boutique</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold">Entreprise</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                <li><a href="{{ route('faq') }}" class="transition hover:text-stone-900">FAQ</a></li>
                <li><a href="{{ route('home') }}#demo" class="transition hover:text-stone-900">Demander une démo</a></li>
                <li><a href="{{ route('admin.login') }}" class="transition hover:text-stone-900">Console admin</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold">Légal</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                <li><a href="{{ route('privacy') }}" class="transition hover:text-stone-900">Confidentialité</a></li>
                <li><a href="{{ route('terms') }}" class="transition hover:text-stone-900">Conditions d'utilisation</a></li>
                <li><a href="{{ route('privacy') }}#rgpd" class="transition hover:text-stone-900">RGPD</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-zinc-100">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-zinc-500 sm:flex-row sm:px-6">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
            <p>Conforme RGPD — export et suppression de vos données sur demande.</p>
        </div>
    </div>
</footer>