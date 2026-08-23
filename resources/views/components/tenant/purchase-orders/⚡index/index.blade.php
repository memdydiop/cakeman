<div class="space-y-6">
    <x-page-header title="Achats" subtitle="Commandes fournisseurs → stock ingrédients" :breadcrumbs="[['label' => 'Achats']]" />
    <flux:card>
        <x-card-header icon="shopping-bag" title="Commandes fournisseur" :actions="[['label' => 'Nouvelle commande', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-purchase\').show()']]" />
        <x-card-body>
            <flux:table :paginate="$this->orders">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Fournisseur</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Ingrédient</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Qté</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Coût total</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->orders as $po)
                        <flux:table.row :key="$po->id">
                            <flux:table.cell>{{ $po->supplier->name }}</flux:table.cell>
                            <flux:table.cell>{{ $po->ingredient->name }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $po->quantity }}{{ $po->ingredient->unit }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold tabular-nums">{{ number_format($po->total_cost,0,',',' ') }} FCFA</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="match($po->status){'received'=>'green','cancelled'=>'zinc',default=>'amber'}">{{ $po->status }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    @if ($po->status === 'pending')
                                        <flux:button size="xs" variant="subtle" icon="check" wire:click="receive({{ $po->id }})">Réception</flux:button>
                                        <flux:button size="xs" variant="subtle" icon="x-mark" wire:click="cancel({{ $po->id }})">Annuler</flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
    <flux:modal name="create-purchase" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div><flux:heading size="lg">Nouvelle commande fournisseur</flux:heading></div>
                <flux:select label="Fournisseur" wire:model="supplier_id" placeholder="Choisir">
                    @foreach ($this->suppliers as $s)
                        <flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select label="Ingrédient" wire:model="ingredient_id" placeholder="Choisir">
                    @foreach ($this->ingredients as $ing)
                        <flux:select.option :value="$ing->id">{{ $ing->name }} (stock {{ $ing->stock_quantity }}{{ $ing->unit }})</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input type="number" step="0.01" label="Quantité" wire:model="quantity" />
                    <flux:input type="number" label="Coût unitaire (FCFA)" wire:model="unit_cost" />
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-purchase').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Commander</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
