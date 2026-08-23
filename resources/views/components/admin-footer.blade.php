<footer class="[grid-area:footer] border-t border-zinc-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2.5 text-[0.813rem] text-zinc-500 sm:flex-row sm:px-6">
        <p>Copyright © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        <div class="flex items-center gap-6">
            <a href="{{ route('privacy') }}" class="transition hover:text-zinc-900">Confidentialité</a>
            <a href="{{ route('terms') }}" class="transition hover:text-zinc-900">Conditions</a>
            <a href="{{ route('faq') }}" class="transition hover:text-zinc-900">FAQ</a>
            <span class="hidden items-center gap-1 sm:flex">Conçu avec <span class="text-red-500">♥</span> par Memdydiop</span>
        </div>
    </div>
</footer>