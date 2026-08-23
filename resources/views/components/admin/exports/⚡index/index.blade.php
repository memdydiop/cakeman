<div>
    <x-page-header title="Exports" subtitle="Factures et avoirs — CSV compta" :breadcrumbs="[['label' => 'Exports']]" />

    <flux:card class="p-6">
        <x-card-header icon="arrow-down-tray" title="Exporter" subtitle="Filtrez puis téléchargez" />
        <x-card-body>
            <div class="flex flex-wrap items-end gap-3">
                <flux:field>
                    <flux:label>Du</flux:label>
                    <flux:input type="date" wire:model="from" />
                </flux:field>
                <flux:field>
                    <flux:label>Au</flux:label>
                    <flux:input type="date" wire:model="to" />
                </flux:field>
                <flux:button icon="arrow-down-tray" :href="route('admin.exports.invoices.csv', array_filter(['from' => $from, 'to' => $to]))" target="_blank">
                    Télécharger CSV
                </flux:button>
            </div>
            <flux:text class="mt-2 text-xs text-zinc-500">Inclut factures + avoirs (montants négatifs).</flux:text>
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="archive-box" title="Exports mensuels stockés" subtitle="Générés le 1er à 04:00" />
        <x-card-body>
            @if (empty($this->storedExports))
                <flux:text class="text-sm text-zinc-500">Aucun export mensuel encore généré.</flux:text>
            @else
                <ul class="divide-y divide-zinc-200">
                    @foreach ($this->storedExports as $file)
                        <li class="flex items-center justify-between py-2">
                            <flux:text class="font-mono text-sm">{{ $file }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ Storage::disk('local')->size($file) }} o</flux:text>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card-body>
    </flux:card>
</div>
