<div>
    <x-page-header title="Avoirs" subtitle="Remboursements et avoirs émis" :breadcrumbs="[['label' => 'Avoirs']]" />
    <flux:card class="p-6">
        <x-card-header icon="receipt-refund" title="Avoirs" :actions="[['label' => 'Nouvel avoir', 'icon' => 'plus', 'wire:click' => 'startCreate']]" />
        <x-card-body>
            <flux:table :paginate="$this->creditNotes">
                <flux:table.columns>
                    <flux:table.column>Boutique</flux:table.column>
                    <flux:table.column>Montant</flux:table.column>
                    <flux:table.column>Motif</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->creditNotes as $note)
                        <flux:table.row :key="$note->id">
                            <flux:table.cell class="text-sm">{{ $note->tenant_id }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($note->amount / 100, 2) }} €</flux:table.cell>
                            <flux:table.cell>{{ $note->reason }}</flux:table.cell>
                            <flux:table.cell>{{ $note->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
    <flux:modal name="credit-note-form" class="md:w-[480px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading>Nouvel avoir</flux:heading>
            <flux:select wire:model="tenantId" label="Boutique" required>
                @foreach ($this->tenants as $t)
                    <flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="amount" type="number" label="Montant (centimes)" required />
            <flux:textarea wire:model="reason" label="Motif" required />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>
</div>