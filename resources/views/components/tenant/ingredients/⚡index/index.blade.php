<div class="space-y-6">
    <x-page-header title="Ingrédients" subtitle="Stock matières premières — coût et seuils" :breadcrumbs="[['label' => 'Ingrédients']]" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-widget icon="beaker" color="amber" title="Valeur stock" :value="number_format($this->totalValorisation,0,',',' ').' FCFA'" trend-text="tous ingrédients" trend-color="text-amber-600" />
        <x-widget icon="exclamation-triangle" color="red" title="Alertes" :value="\App\Models\Ingredient::whereNotNull('low_stock_threshold')->whereColumn('stock_quantity','<=','low_stock_threshold')->count()" trend-text="seuils atteints" trend-color="text-red-600" />
        <x-widget icon="cube" color="blue" title="Références" :value="$this->ingredients->total()" trend-text="ingrédients" trend-color="text-blue-600" />
    </div>

    <flux:card>
        <x-card-header icon="beaker" title="Ingrédients" subtitle="Valorisation {{ number_format($this->totalValorisation,0,',',' ') }} FCFA" :actions="[
            ['label' => 'Nouvel ingrédient', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-ingredient\').show()'],
        ]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Nom…" :per-page="null" />
            <flux:table :paginate="$this->ingredients">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Nom</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Unité</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Stock</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Coût/u</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Valeur</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->ingredients as $ing)
                        <flux:table.row :key="$ing->id">
                            <flux:table.cell class="font-medium">{{ $ing->name }} @if ($ing->isLowStock()) <flux:badge size="sm" color="red">Stock faible</flux:badge> @endif</flux:table.cell>
                            <flux:table.cell>{{ $ing->unit }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $ing->stock_quantity }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ number_format($ing->unit_cost, 0, ',', ' ') }} FCFA</flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold tabular-nums">{{ number_format((float) $ing->stock_quantity * $ing->unit_cost,0,',',' ') }} FCFA</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" variant="subtle" icon="plus" wire:click="adjustStock({{ $ing->id }}, 1)" />
                                    <flux:button size="xs" variant="subtle" icon="minus" wire:click="adjustStock({{ $ing->id }}, -1)" />
                                    <flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $ing->id }})" wire:confirm="Supprimer ?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-ingredient" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvel ingrédient</flux:heading>
                    <flux:subheading>Stock initial et coût unitaire</flux:subheading>
                </div>
                <flux:input label="Nom" wire:model="name" />
                <flux:input label="Unité (kg, L, pcs)" wire:model="unit" />
                <flux:input type="number" step="0.01" label="Stock" wire:model="stock_quantity" />
                <flux:input type="number" label="Coût / unité (FCFA)" wire:model="unit_cost" />
                <flux:input type="number" step="0.01" label="Seuil alerte" wire:model="low_stock_threshold" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-ingredient').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
