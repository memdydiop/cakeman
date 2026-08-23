<div class="space-y-6">
    <x-page-header title="Catégories" subtitle="Organisez votre catalogue" :breadcrumbs="[['label' => 'Catégories']]" />

    <flux:card>
        <x-card-header icon="tag" title="Catégories" subtitle="Arborescence et activation" :actions="[
            ['label' => 'Nouvelle catégorie', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-category\').show()'],
        ]" />
        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Nom</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Parent</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->categories as $cat)
                        <flux:table.row :key="$cat->id">
                            <flux:table.cell class="font-medium">{{ $cat->name }}</flux:table.cell>
                            <flux:table.cell>{{ $cat->parent?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$cat->is_active ? 'green' : 'zinc'">{{ $cat->is_active ? 'Active' : 'Inactive' }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" variant="subtle" icon="pencil" wire:click="startEdit({{ $cat->id }})" />
                                    <flux:button size="xs" variant="subtle" icon="power" wire:click="toggle({{ $cat->id }})" />
                                    <flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $cat->id }})" wire:confirm="Supprimer ?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-category" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvelle catégorie</flux:heading>
                    <flux:subheading>Nom et parent optionnel</flux:subheading>
                </div>
                <flux:input label="Nom" wire:model="name" />
                <flux:select label="Parent" wire:model="parent_id" placeholder="Aucun">
                    @foreach ($this->parentOptions as $opt)
                        <flux:select.option :value="$opt->id">{{ $opt->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-category').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-category" variant="flyout">
        <form wire:submit="update">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Renommer</flux:heading>
                </div>
                <flux:input label="Nom" wire:model="editingName" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('edit-category').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Enregistrer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
