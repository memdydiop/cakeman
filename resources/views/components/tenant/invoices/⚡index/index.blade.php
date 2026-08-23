<div class="space-y-6">
    <x-page-header title="Factures" subtitle="Facturation client — devis et encaissements" :breadcrumbs="[['label' => 'Factures']]" />

    <flux:card>
        <x-card-header icon="receipt-percent" title="Factures" :actions="[
            ['label' => 'Nouvelle facture', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-invoice\').show()'],
        ]" />
        <x-card-body>
            <flux:table :paginate="$this->invoices">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">N°</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Client</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Commande</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Montant</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->invoices as $inv)
                        <flux:table.row :key="$inv->id">
                            <flux:table.cell class="font-mono text-sm">{{ $inv->number }}</flux:table.cell>
                            <flux:table.cell>{{ $inv->client?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-sm">#{{ $inv->order_id ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-semibold tabular-nums">{{ number_format($inv->amount,0,',',' ') }} FCFA</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="match($inv->status){'paid'=>'green','cancelled'=>'zinc',default=>'amber'}">{{ $inv->status }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    @if ($inv->status === 'draft')
                                        <flux:button size="xs" variant="subtle" icon="check" wire:click="markPaid({{ $inv->id }})">Payer</flux:button>
                                        <flux:button size="xs" variant="subtle" icon="x-mark" wire:click="cancel({{ $inv->id }})">Annuler</flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-invoice" variant="flyout">
        <form wire:submit="generate">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvelle facture</flux:heading>
                    <flux:subheading>À partir d'une commande livrée</flux:subheading>
                </div>
                <flux:select label="Commande" wire:model="order_id" placeholder="Choisir">
                    @foreach ($this->pendingOrders as $order)
                        <flux:select.option :value="$order->id">#{{ $order->id }} — {{ $order->client?->name ?? 'Sans client' }} — {{ number_format($order->total,0,',',' ') }} FCFA</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-invoice').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Générer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
