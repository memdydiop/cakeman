<div class="space-y-6">
    <x-page-header
        title="Sessions support"
        subtitle="Accès temporaires en lecture seule sur le schéma d'une boutique"
        :breadcrumbs="[
            [
                'label' => __('Sessions support'),
            ],
        ]" />

    <flux:card>
        <x-card-header icon="user-circle" title="Sessions support"
            subtitle="Motif obligatoire, durée limitée, expiration automatique" :actions="[
                [
                    'label' => __('Nouvelle session'),
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'start-support-session\').show()',
                ],
            ]" />

        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher (motif, opérateur, boutique)…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.status" size="sm" class="w-44">
                        <flux:select.option value="all">{{ __('Tous les statuts') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Actives') }}</flux:select.option>
                        <flux:select.option value="expired">{{ __('Expirées') }}</flux:select.option>
                        <flux:select.option value="ended">{{ __('Terminées') }}</flux:select.option>
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->sessions">
                <flux:table.columns>
                    <flux:table.column>Boutique</flux:table.column>
                    <flux:table.column>Motif</flux:table.column>
                    <flux:table.column>Opérateur</flux:table.column>
                    <flux:table.column>Durée</flux:table.column>
                    <flux:table.column>Début</flux:table.column>
                    <flux:table.column>Expiration</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->sessions as $session)
                        <flux:table.row :key="$session->id">
                            <flux:table.cell>
                                <div class="font-medium">{{ $session->tenant?->name ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $session->tenant?->domains->first()?->domain ?? '' }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs">
                                <div class="line-clamp-2">{{ $session->reason }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium">{{ $session->adminUser?->name ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $session->adminUser?->email ?? '' }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $session->duration_minutes }} min</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $session->started_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $session->expires_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match ($session->status()) {
                                    'active' => 'green',
                                    'expired' => 'red',
                                    'ended' => 'zinc',
                                }">{{ $this->statusLabel($session->status()) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($session->isActive())
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button variant="subtle" size="xs" icon="eye"
                                            :tooltip="__('Voir les données (lecture seule)')"
                                            wire:click="showSnapshot({{ $session->id }})" />
                                        <flux:button variant="subtle" size="xs" icon="x-circle"
                                            :tooltip="__('Terminer')" wire:click="endSession({{ $session->id }})"
                                            wire:confirm="Terminer cette session support ?" />
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="start-support-session" variant="flyout">
        <form wire:submit="startSession">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvelle session support</flux:heading>
                    <flux:subheading>Ouverture d'un accès temporaire en lecture seule sur une boutique</flux:subheading>
                </div>

                <flux:select label="Boutique" wire:model="sessionTenantId" placeholder="Choisir une boutique…">
                    @foreach ($this->tenantOptions as $option)
                        <flux:select.option :value="$option['id']">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Durée" wire:model="sessionDurationMinutes" placeholder="Choisir une durée…">
                    @foreach ($this->durationOptions() as $minutes)
                        <flux:select.option :value="$minutes">{{ $minutes }} minutes</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea label="Motif (obligatoire)" wire:model="sessionReason" rows="3"
                    placeholder="Expliquez la raison de cet accès…" />

                <div class="flex items-center justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('start-support-session').close()">
                        {{ __('Annuler') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Ouvrir la session') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="support-session-snapshot" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Données en lecture seule</flux:heading>
                <flux:subheading>Métriques de la boutique — aucune écriture possible</flux:subheading>
            </div>

            @if ($this->snapshotSessionId)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:subheading>Utilisateurs</flux:subheading>
                        <flux:text class="text-2xl! font-semibold">{{ $this->snapshot['users_total'] }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading>Utilisateurs actifs</flux:subheading>
                        <flux:text class="text-2xl! font-semibold">{{ $this->snapshot['users_active'] }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading>Invitations en attente</flux:subheading>
                        <flux:text class="text-2xl! font-semibold">{{ $this->snapshot['invitations_pending'] }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading>Emplois en file</flux:subheading>
                        <flux:text class="text-2xl! font-semibold">{{ $this->snapshot['jobs_pending'] }}</flux:text>
                    </div>
                    <div>
                        <flux:subheading>Emplois échoués</flux:subheading>
                        <flux:text class="text-2xl! font-semibold">{{ $this->snapshot['jobs_failed'] }}</flux:text>
                    </div>
                </dl>
            @endif

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('support-session-snapshot').close()">
                    {{ __('Fermer') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>