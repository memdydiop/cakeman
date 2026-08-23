<div class="space-y-6">
    <x-page-header title="Reporting" subtitle="CA par période & marge recettes vs commandes" :breadcrumbs="[['label' => 'Reporting']]" />

    <flux:card>
        <x-card-body>
            <div class="flex flex-wrap gap-3 items-end">
                <flux:input type="date" label="Du" wire:model.live="from" />
                <flux:input type="date" label="Au" wire:model.live="to" />
            </div>
        </x-card-body>
    </flux:card>

    @php $s = $this->stats; @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="banknotes" color="green" title="CA" :value="number_format($s['ca'],0,',',' ').' FCFA'" :trend-text="$s['count'].' commandes'" trend-color="text-emerald-600" />
        <x-widget icon="cube" color="amber" title="Coût recettes" :value="number_format($s['cost'],0,',',' ').' FCFA'" trend-text="matières" trend-color="text-amber-600" />
        <x-widget icon="chart-bar-square" color="indigo" title="Marge" :value="number_format($s['margin'],0,',',' ').' FCFA'" :trend-text="$s['marginPct'].' %'" :trend-color="$s['marginPct']>=0 ? 'text-green-600' : 'text-red-600'" />
        <x-widget icon="receipt-percent" color="blue" title="Remises" :value="number_format($s['discount'],0,',',' ').' FCFA'" trend-text="total période" trend-color="text-blue-600" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="p-6 lg:col-span-2">
            <x-card-header icon="chart-bar-square" title="CA journalier" subtitle="Évolution sur la période" />
            <x-card-body>
                <div class="h-48">
                    <chart:area
                        :series="[['name' => 'CA', 'data' => $this->dailySeries['data']]]"
                        :categories="$this->dailySeries['categories']"
                        colors='["#059669"]'
                        height="192"
                    />
                </div>
            </x-card-body>
        </flux:card>

        <flux:card class="p-6">
            <x-card-header icon="trophy" title="Top marge" subtitle="Produits les plus rentables" />
            <x-card-body>
                <div class="space-y-3">
                    @forelse ($this->topProductsByMargin as $row)
                        <div class="flex justify-between text-sm">
                            <span class="truncate">{{ $row['product']?->name ?? '—' }}</span>
                            <span class="font-semibold tabular-nums {{ $row['margin']>=0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($row['margin'],0,',',' ') }} FCFA</span>
                        </div>
                        <div class="text-xs text-zinc-500">CA {{ number_format($row['revenue'],0,',',' ') }} · Coût {{ number_format($row['cost'],0,',',' ') }}</div>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Aucune donnée.</flux:text>
                    @endforelse
                </div>
            </x-card-body>
        </flux:card>
    </div>
</div>
