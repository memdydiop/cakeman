<div class="space-y-6">
    <x-page-header title="Templates email" subtitle="Sujets et corps éditables — variables {{tenant_name}} etc." :breadcrumbs="[['label' => 'Templates']]" />

    <flux:card>
        <x-card-header icon="envelope" title="Modèles" subtitle="4 modèles seeded, éditables — variables {{tenant_name}} etc." />
        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Clé</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Sujet</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Actif</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->templates as $t)
                        <flux:table.row :key="$t->id">
                            <flux:table.cell class="font-mono text-sm">{{ $t->key }}</flux:table.cell>
                            <flux:table.cell class="max-w-md truncate">{{ $t->subject }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$t->is_active ? 'green' : 'zinc'">{{ $t->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                            <flux:table.cell><flux:button size="xs" variant="subtle" icon="pencil" wire:click="edit({{ $t->id }})">Éditer</flux:button></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="edit-template" variant="flyout">
        <form wire:submit="save">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Éditer template</flux:heading>
                    <flux:subheading>Variables : {{tenant_name}}, {{domain}}, {{number}}, {{amount}}</flux:subheading>
                </div>
                <flux:input label="Sujet" wire:model="subject" />
                <flux:textarea label="Corps" wire:model="body" rows="6" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('edit-template').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Enregistrer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
