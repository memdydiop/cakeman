<div class="space-y-6">
    <x-page-header title="Promos" subtitle="Coupons fidélité — remises client" :breadcrumbs="[['label' => 'Promos']]" />
    <flux:card>
        <x-card-header icon="ticket" title="Coupons" :actions="[['label' => 'Nouveau coupon', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-coupon\').show()']]" />
        <x-card-body>
            <flux:table :paginate="$this->coupons">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Code</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Type</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Valeur</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->coupons as $c)
                        <flux:table.row :key="$c->id">
                            <flux:table.cell class="font-mono font-medium">{{ $c->code }}</flux:table.cell>
                            <flux:table.cell>{{ $c->type }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $c->value }}{{ $c->type === 'percent' ? '%' : ' FCFA' }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$c->isValid() ? 'green' : 'zinc'">{{ $c->isValid() ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                            <flux:table.cell><div class="flex justify-end gap-1"><flux:button size="xs" variant="subtle" icon="power" wire:click="toggle({{ $c->id }})" /><flux:button size="xs" variant="subtle" icon="trash" wire:click="delete({{ $c->id }})" wire:confirm="Supprimer ?" /></div></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
    <flux:modal name="create-coupon" variant="flyout">
        <form wire:submit="create">
            <div class="space-y-6">
                <div><flux:heading size="lg">Nouveau coupon</flux:heading></div>
                <flux:input label="Code" wire:model="code" placeholder="FIDELE10" />
                <flux:select label="Type" wire:model="type">
                    <flux:select.option value="percent">Pourcentage</flux:select.option>
                    <flux:select.option value="fixed">Fixe FCFA</flux:select.option>
                </flux:select>
                <flux:input type="number" label="Valeur" wire:model="value" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-coupon').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
