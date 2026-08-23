<div>
    <x-page-header title="Coupons" subtitle="Codes promo pour les abonnements" :breadcrumbs="[['label' => 'Coupons']]" />

    <flux:card class="p-6">
        <x-card-header icon="tag" title="Coupons" :actions="[['label' => 'Nouveau coupon', 'icon' => 'plus', 'wire:click' => 'startCreate']]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher un code…" />
            <flux:table :paginate="$this->coupons">
                <flux:table.columns>
                    <flux:table.column>Code</flux:table.column>
                    <flux:table.column>Réduction</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>Utilisations</flux:table.column>
                    <flux:table.column>Expire le</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->coupons as $coupon)
                        <flux:table.row :key="$coupon->id">
                            <flux:table.cell class="font-mono font-medium">{{ $coupon->code }}</flux:table.cell>
                            <flux:table.cell>{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : number_format($coupon->discount_value / 100, 2).' €' }}</flux:table.cell>
                            <flux:table.cell><flux:badge :color="$coupon->isValid() ? 'green' : 'zinc'">{{ $coupon->isValid() ? 'Valide' : 'Invalide' }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $coupon->used_count }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }}</flux:table.cell>
                            <flux:table.cell>{{ $coupon->expires_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown><flux:button variant="subtle" size="xs" icon="ellipsis-vertical" /><flux:menu>
                                    <flux:menu.item icon="pencil" title="Modifier" wire:click="startEditing({{ $coupon->id }})" />
                                    <flux:menu.item :icon="$coupon->is_active ? 'pause' : 'play'" :title="$coupon->is_active ? 'Désactiver' : 'Activer'" wire:click="toggle({{ $coupon->id }})" />
                                </flux:menu></flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="coupon-form" class="md:w-[480px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading>{{ $editingId ? 'Modifier le coupon' : 'Nouveau coupon' }}</flux:heading>
            <flux:input wire:model="code" label="Code" placeholder="BIENVENUE10" required />
            <flux:select wire:model="discount_type" label="Type"><flux:select.option value="percentage">Pourcentage</flux:select.option><flux:select.option value="fixed">Montant fixe (centimes)</flux:select.option></flux:select>
            <flux:input wire:model="discount_value" type="number" label="Valeur" required />
            <flux:input wire:model="max_uses" type="number" label="Utilisations max (vide = illimité)" />
            <flux:input wire:model="expires_at" type="datetime-local" label="Expire le" />
            <flux:checkbox wire:model="is_active" label="Actif" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>
</div>