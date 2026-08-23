<div class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm">
    @if ($this->submitted)
        <div class="rounded-xl bg-green-50 px-4 py-6 text-center">
            <span class="inline-flex size-10 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span>
            <h3 class="mt-3 font-display text-lg font-semibold text-green-900">Merci !</h3>
            <p class="mt-1 text-sm text-green-700">Votre demande a bien été enregistrée. Notre équipe vous recontacte très vite.</p>
        </div>
    @else
        <h3 class="font-display text-xl font-semibold tracking-tight">Demande de démonstration</h3>
        <p class="mt-1 text-sm text-zinc-600">Une question ? Laissez-nous vos coordonnées, on vous rappelle sous 24 h.</p>

        <form wire:submit="submit" class="mt-5 space-y-4">
            <div>
                <label for="lead-name" class="text-sm font-medium text-stone-800">Nom complet</label>
                <input id="lead-name" wire:model="name" type="text" required
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200" />
                <flux:error name="name" />
            </div>

            <div>
                <label for="lead-email" class="text-sm font-medium text-stone-800">E-mail professionnel</label>
                <input id="lead-email" wire:model="email" type="email" required
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200" />
                <flux:error name="email" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="lead-phone" class="text-sm font-medium text-stone-800">Téléphone</label>
                    <input id="lead-phone" wire:model="phone" type="tel" placeholder="07 12 34 56 78"
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200" />
                    <flux:error name="phone" />
                </div>

                <div>
                    <label for="lead-shop" class="text-sm font-medium text-stone-800">Nom de la boutique</label>
                    <input id="lead-shop" wire:model="shopName" type="text"
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200" />
                    <flux:error name="shopName" />
                </div>
            </div>

            <div>
                <label for="lead-notes" class="text-sm font-medium text-stone-800">Votre besoin (facultatif)</label>
                <textarea id="lead-notes" wire:model="notes" rows="3"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"></textarea>
                <flux:error name="notes" />
            </div>

            <button type="submit" class="w-full rounded-full bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-700">
                Demander une démonstration
            </button>

            <p class="text-xs text-zinc-400">Sans engagement. Vos données restent confidentielles (RGPD).</p>
        </form>
    @endif
</div>