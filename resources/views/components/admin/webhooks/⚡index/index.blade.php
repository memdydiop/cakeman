<div class="space-y-6">
    <x-page-header
        title="Webhooks"
        subtitle="Journal des confirmations de paiement Mobile Money"
        :breadcrumbs="[
            [
                'label' => __('Webhooks'),
            ],
        ]" />

    <flux:card>
        <x-card-header icon="arrow-path" title="Webhooks de paiement"
            subtitle="Payload brut, signature vérifiée, rejeu manuel" />

        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher (référence, fournisseur, erreur, facture)…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.status" size="sm" class="w-44">
                        <flux:select.option value="all">{{ __('Tous les statuts') }}</flux:select.option>
                        <flux:select.option value="processed">{{ __('Traités') }}</flux:select.option>
                        <flux:select.option value="received">{{ __('Reçus') }}</flux:select.option>
                        <flux:select.option value="duplicate">{{ __('Doublons') }}</flux:select.option>
                        <flux:select.option value="ignored">{{ __('Ignorés') }}</flux:select.option>
                        <flux:select.option value="mismatch">{{ __('Écarts de montant') }}</flux:select.option>
                        <flux:select.option value="failed">{{ __('Échecs') }}</flux:select.option>
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->logs">
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Fournisseur</flux:table.column>
                    <flux:table.column>Événement</flux:table.column>
                    <flux:table.column>Référence</flux:table.column>
                    <flux:table.column>Facture</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>Erreur</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->logs as $log)
                        <flux:table.row :key="$log->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $log->provider }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $log->event }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap font-mono text-xs">{{ $log->provider_ref ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $log->invoice?->number ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$this->statusColor($log->status)">{{ $log->statusLabel() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs">
                                <div class="line-clamp-2 text-xs text-zinc-500">{{ $log->error ?? '—' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button variant="subtle" size="xs" icon="document-text"
                                        :tooltip="__('Voir le payload')"
                                        wire:click="showPayload({{ $log->id }})" />
                                    @if ($log->signature_valid && in_array($log->status, ['failed', 'mismatch'], true))
                                        <flux:button variant="subtle" size="xs" icon="arrow-path"
                                            :tooltip="__('Rejouer')"
                                            wire:click="replay({{ $log->id }})"
                                            wire:confirm="Rejouer ce webhook ?" />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="webhook-payload" class="md:w-[40rem]">
        <flux:heading size="lg">Payload du webhook</flux:heading>

        @if ($this->payloadLog)
            <flux:text class="mt-1 text-xs text-zinc-500">
                {{ $this->payloadLog->provider }} · {{ $this->payloadLog->event }} · {{ $this->payloadLog->created_at?->format('d/m/Y H:i:s') }}
            </flux:text>
            <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-zinc-950 p-4 font-mono text-xs text-zinc-100">{{ json_encode($this->payloadLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        @endif

        <div class="mt-4 flex items-center justify-end">
            <flux:button variant="ghost" x-on:click="$flux.modal('webhook-payload').close()">
                {{ __('Fermer') }}
            </flux:button>
        </div>
    </flux:modal>
</div>