<div class="space-y-6">
    <x-page-header title="Produits" subtitle="Catalogue — prix en FCFA" :breadcrumbs="[['label' => 'Produits']]" />

    <flux:card>
        <x-card-header icon="cube" title="Produits" :actions="[
            ['label' => 'Nouveau produit', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-product\').show()'],
        ]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Nom, SKU…" :per-page="null">
                <x-slot name="filters">
                    <flux:select wire:model.live="filterCategory" size="sm" class="w-44" placeholder="Toutes catégories">
                        <flux:select.option value="">Toutes</flux:select.option>
                        @foreach ($this->categoryOptions as $cat)
                            <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->products">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Nom</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Catégorie</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">SKU</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Prix</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->products as $product)
                        <flux:table.row :key="$product->id">
                            <flux:table.cell class="font-medium">{{ $product->name }}</flux:table.cell>
                            <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">{{ $product->sku ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold tabular-nums">{{ number_format($product->price, 0, ',', ' ') }} FCFA</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$product->is_active ? 'green' : 'zinc'">{{ $product->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" variant="subtle" icon="power" wire:click="toggle({{ $product->id }})" />
                                    <flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $product->id }})" wire:confirm="Supprimer ?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-product" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouveau produit</flux:heading>
                    <flux:subheading>Prix en FCFA, catégorie optionnelle</flux:subheading>
                </div>
                <flux:input label="Nom" wire:model="name" />
                <flux:select label="Catégorie" wire:model="category_id" placeholder="Aucune">
                    @foreach ($this->categoryOptions as $cat)
                        <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="SKU" wire:model="sku" placeholder="Optionnel" />
                <flux:input type="number" label="Prix (FCFA)" wire:model="price" />
                <flux:switch label="Actif" wire:model="is_active" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-product').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
