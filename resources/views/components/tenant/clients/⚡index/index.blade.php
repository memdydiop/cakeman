<div class="space-y-6">
    <x-page-header title="Clients" subtitle="Base clients — fidélisation" :breadcrumbs="[['label' => 'Clients']]" />

    <flux:card>
        <x-card-header icon="users" title="Clients" :actions="[
            ['label' => 'Nouveau client', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-client\').show()'],
        ]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Nom, téléphone…" :per-page="null" />
            <flux:table :paginate="$this->clients">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Nom</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Type</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Téléphone</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->clients as $client)
                        <flux:table.row :key="$client->id">
                            <flux:table.cell>
                                <div class="font-medium">{{ $client->name }}</div>
                                @if ($client->preferences)<div class="text-xs text-zinc-500 line-clamp-1">{{ $client->preferences }}</div>@endif
                            </flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" color="zinc">{{ $client->type }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $client->phone ?? '—' }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$client->is_active ? 'green' : 'zinc'">{{ $client->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" variant="subtle" icon="power" wire:click="toggle({{ $client->id }})" />
                                    <flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $client->id }})" wire:confirm="Supprimer ?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-client" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouveau client</flux:heading>
                </div>
                <flux:input label="Nom" wire:model="name" />
                <flux:select label="Type" wire:model="type">
                    <flux:select.option value="particulier">Particulier</flux:select.option>
                    <flux:select.option value="pro">Pro</flux:select.option>
                </flux:select>
                <flux:input label="Téléphone" wire:model="phone" />
                <flux:input label="Email" wire:model="email" />
                <flux:input type="date" label="Anniversaire" wire:model="birthday" />
                <flux:textarea label="Préférences / allergies" wire:model="preferences" rows="2" placeholder="Ex: sans arachide, goût vanille…" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-client').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
