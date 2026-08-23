<div>
    <x-page-header title="Clés API" subtitle="Accès programmatique à la console" :breadcrumbs="[['label' => 'Clés API']]" />
    <flux:card class="p-6">
        <x-card-header icon="key" title="Clés" :actions="[['label' => 'Nouvelle clé', 'icon' => 'plus', 'wire:click' => '$refresh']]" />
        <x-card-body>
            <form wire:submit="create" class="flex gap-2 mb-4">
                <flux:input wire:model="name" placeholder="Nom de la clé" required class="flex-1" />
                <flux:button type="submit" variant="primary">Créer</flux:button>
            </form>
            @if ($plainToken)
                <flux:callout color="amber" icon="key" class="mb-4"><flux:callout.text>Clé : <span class="font-mono break-all">{{ $plainToken }}</span> — copiez-la, elle ne sera plus affichée.</flux:callout.text></flux:callout>
            @endif
            <flux:table :paginate="$this->tokens">
                <flux:table.columns>
                    <flux:table.column>Nom</flux:table.column>
                    <flux:table.column>Créée le</flux:table.column>
                    <flux:table.column>Dernière utilisation</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->tokens as $t)
                        <flux:table.row :key="$t->id">
                            <flux:table.cell>{{ $t->name }}</flux:table.cell>
                            <flux:table.cell>{{ $t->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>{{ $t->last_used_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end"><flux:button variant="ghost" size="xs" icon="trash" wire:click="revoke({{ $t->id }})" wire:confirm="Révoquer ?" /></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
</div>