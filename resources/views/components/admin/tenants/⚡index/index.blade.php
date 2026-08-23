<div class="space-y-6">
    <x-page-header
        title="Boutiques"
        subtitle="Tous les tenants de la plateforme"
        :breadcrumbs="[
            [
                'label' => __('Boutiques')
            ],
        ]" />

    <flux:card>
        <x-card-header icon="building-storefront" title="Liste des boutiques"
            subtitle="Toutes les boutiques de la plateforme" :actions="[
                [
                    'label' => 'Nouvelle boutique',
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'create-tenant\').show()',
                ],
            ]" />

        <x-card-body class="">

            <x-table-toolbar search-placeholder="Rechercher une boutique…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.status" size="sm" class="w-40">
                        <flux:select.option value="all">{{ __('Tous') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Actif') }}</flux:select.option>
                        <flux:select.option value="pending">{{ __('En attente') }}</flux:select.option>
                        <flux:select.option value="suspended">{{ __('Suspendu') }}</flux:select.option>
                        <flux:select.option value="trialing">{{ __('Essai') }}</flux:select.option>
                        <flux:select.option value="cancelled">{{ __('Annulé') }}</flux:select.option>
                    </flux:select>
                </x-slot>
                <x-slot name="actions">
                    <flux:button variant="subtle" size="sm" icon="arrow-down-tray" wire:click="exportCsv">{{ __('Exporter CSV') }}</flux:button>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->tenants">
                <flux:table.columns>
                    <flux:table.column>Nom</flux:table.column>
                    <flux:table.column>Domaine</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>Créée le</flux:table.column>
                    <flux:table.column align="end"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->tenants as $tenant)
                        <flux:table.row :key="$tenant->id">
                            <flux:table.cell class="font-medium">{{ $tenant->name }}</flux:table.cell>
                            <flux:table.cell>{{ $tenant->domains->first()?->domain }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match ($tenant->status) {
                                    \App\Enums\TenantStatus::Active => 'green',
                                    \App\Enums\TenantStatus::Pending => 'blue',
                                    \App\Enums\TenantStatus::Suspended => 'red',
                                    \App\Enums\TenantStatus::Trialing => 'amber',
                                    \App\Enums\TenantStatus::Cancelled => 'zinc',
                                }">
                                    {{ $this->statusLabel($tenant->status) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell >{{ $tenant->created_at?->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown>
                                    <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" title="Voir" :href="route('admin.tenants.show', $tenant)"
                                            wire:navigate />
                                    </flux:menu>

                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-tenant" class="md:w-[28rem]">
        <livewire:admin.tenants.create />
    </flux:modal>
</div>
