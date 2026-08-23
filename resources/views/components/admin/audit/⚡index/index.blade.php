<div class="space-y-6">
    <x-page-header
        title="Audit"
        subtitle="Journal immuable des actions de la console"
        :breadcrumbs="[
            [
                'label' => __('Audit'),
            ],
        ]" />

    <flux:card>
        <x-card-header icon="clipboard-document-list" title="Journal d'audit"
            subtitle="Chaque action sensible de la plateforme est tracée" />

        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher (action, e-mail, boutique, IP)…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.action" size="sm" class="w-52">
                        <flux:select.option value="all">{{ __('Toutes les actions') }}</flux:select.option>
                        @foreach ($this->actions as $action)
                            <flux:select.option :value="$action">{{ $this->actionLabel($action) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->logs">
                <flux:table.columns>
                    <flux:table.column>Date & heure</flux:table.column>
                    <flux:table.column>Opérateur</flux:table.column>
                    <flux:table.column>Action</flux:table.column>
                    <flux:table.column>Boutique</flux:table.column>
                    <flux:table.column>Détails</flux:table.column>
                    <flux:table.column>IP</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->logs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($log->adminUser)
                                    <div class="font-medium">{{ $log->adminUser->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->adminUser->email }}</div>
                                @else
                                    <span class="text-zinc-400">{{ __('Système') }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $this->actionLabel($log->action) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $log->tenant?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $this->details($log->properties) }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap font-mono text-xs">{{ $log->ip_address ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
</div>