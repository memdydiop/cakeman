<div>
    <x-page-header
        title="Journal de connexions"
        subtitle="Tentatives d'accès à la console (CdC § 12)"
        :breadcrumbs="[['label' => 'Journal de connexions']]" />

    <flux:card class="p-6">
        <x-card-header icon="lock-closed" title="Tentatives" subtitle="Filtre par e-mail et résultat" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher par e-mail…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="success" size="sm" class="w-40">
                        <flux:select.option value="all">{{ __('Tous') }}</flux:select.option>
                        <flux:select.option value="success">{{ __('Réussies') }}</flux:select.option>
                        <flux:select.option value="failure">{{ __('Échouées') }}</flux:select.option>
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->logs">
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>E-mail</flux:table.column>
                    <flux:table.column>Résultat</flux:table.column>
                    <flux:table.column>2FA</flux:table.column>
                    <flux:table.column>IP</flux:table.column>
                    <flux:table.column>Navigateur</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->logs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell class="text-sm">{{ $log->created_at->format('d/m/Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">{{ $log->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$log->success ? 'green' : 'red'">
                                    {{ $log->success ? 'Réussie' : 'Échouée' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($log->two_factor)
                                    <flux:badge color="blue">2FA</flux:badge>
                                @else
                                    <flux:text class="text-sm text-zinc-400">—</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">{{ $log->ip_address ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate text-sm">{{ $log->user_agent ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
</div>