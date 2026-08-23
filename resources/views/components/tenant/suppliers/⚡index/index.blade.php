<div class="space-y-6">
    <x-page-header title="Fournisseurs" subtitle="Achats — approvisionnement ingrédients" :breadcrumbs="[['label' => 'Fournisseurs']]" />
    <flux:card>
        <x-card-header icon="truck" title="Fournisseurs" :actions="[['label' => 'Nouveau fournisseur', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-supplier\').show()']]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Nom…" :per-page="null" />
            <flux:table :paginate="$this->suppliers">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Nom</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Téléphone</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->suppliers as $s)
                        <flux:table.row :key="$s->id">
                            <flux:table.cell class="font-medium">{{ $s->name }}</flux:table.cell>
                            <flux:table.cell>{{ $s->phone ?? '—' }}</flux:table.cell>
                            <flux:table.cell><div class="flex justify-end"><flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $s->id }})" wire:confirm="Supprimer ?" /></div></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
    <flux:modal name="create-supplier" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div><flux:heading size="lg">Nouveau fournisseur</flux:heading></div>
                <flux:input label="Nom" wire:model="name" />
                <flux:input label="Téléphone" wire:model="phone" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-supplier').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
