<div>
    <x-page-header
        title="Factures"
        subtitle="Facturation Mobile Money — liens de paiement et encaissements"
        :breadcrumbs="[['label' => 'Factures']]" />

    <flux:card class="p-6">
        <x-card-header icon="receipt-percent" title="Factures" subtitle="Filtres et recherche" />

        <x-card-body>
            <div class="flex flex-wrap items-center gap-3">
                <flux:input wire:model.live.debounce="search" placeholder="N° facture, référence ou boutique…" class="w-72" icon="magnifying-glass" />
                <flux:select wire:model.live="filters.status" class="w-44">
                    <option value="all">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="paid">Payée</option>
                    <option value="overdue">En retard</option>
                    <option value="failed">Échouée</option>
                    <option value="void">Annulée</option>
                </flux:select>
            </div>
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 mt-4">
        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">N°</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Boutique</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Période</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Montant</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Lien de paiement</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->invoices as $invoice)
                        <flux:table.row :key="$invoice->id" class="hover:bg-zinc-50/80">
                            <flux:table.cell class="whitespace-nowrap font-medium tabular-nums">{{ $invoice->number }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $invoice->subscription?->tenant?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($invoice->period_start && $invoice->period_end)
                                    <span class="whitespace-nowrap text-sm tabular-nums">{{ $invoice->period_start->format('d/m/Y') }} → {{ $invoice->period_end->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-sm text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap font-semibold tabular-nums">{{ $this->formatAmount($invoice->amount) }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($invoice->payment_link_url)
                                    <span class="font-mono text-xs text-zinc-500">{{ $invoice->payment_link_url }}</span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$this->statusColor($invoice->status)">
                                    {{ $this->statusLabel($invoice->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($invoice->payment_link_url)
                                    <flux:button variant="ghost" size="xs" icon="arrow-top-right-on-square" :href="$invoice->payment_link_url" target="_blank" title="Ouvrir le lien" />
                                @endif

                                @if (in_array($invoice->status->value, ['pending', 'overdue'], true))
                                    <flux:button variant="ghost" size="xs" icon="check-circle" title="Simuler le paiement (sandbox)"
                                        wire:click="simulatePayment({{ $invoice->id }})"
                                        wire:confirm="Confirmer le paiement de la facture {{ $invoice->number }} (sandbox) ?" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-zinc-500">
                                {{ __('Aucune facture.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $this->invoices->links() }}
            </div>
        </x-card-body>
    </flux:card>
</div>