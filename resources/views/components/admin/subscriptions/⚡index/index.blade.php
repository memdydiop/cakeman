<div class="space-y-6">
    <x-page-header title="Abonnements" subtitle="Plans, essais et factures des boutiques" />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-widget icon="banknotes" color="green" title="MRR" :value="number_format($this->mrr, 0, ',', ' ') . ' FCFA'" />
        <x-widget icon="shield-check" color="blue" title="Actifs" :value="$this->activeCount" />
        <x-widget icon="clock" color="amber" title="En essai" :value="$this->trialingCount" />
        <x-widget icon="shield-exclamation" color="red" title="En retard" :value="$this->pastDueCount" />
    </div>

    <flux:card class="p-6">
        <x-card-header icon="credit-card" title="Abonnements des boutiques" subtitle="Statut, plan et prochaine échéance" />

        <x-card-body>
            <flux:table :paginate="$this->tenants">
                <flux:table.columns>
                    <flux:table.column>Boutique</flux:table.column>
                    <flux:table.column>Plan</flux:table.column>
                    <flux:table.column>Période</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>Échéance</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->tenants as $tenant)
                        <flux:table.row :key="$tenant->id">
                            <flux:table.cell class="font-medium">{{ $tenant->name }}</flux:table.cell>
                            <flux:table.cell>{{ $tenant->subscription?->plan->name ?? 'Gratuit' }}</flux:table.cell>
                            <flux:table.cell>{{ $this->periodLabel($tenant->subscription?->billing_period) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($tenant->subscription?->status) {
                                        \App\Enums\SubscriptionStatus::Trialing => 'amber',
                                        \App\Enums\SubscriptionStatus::Active => 'green',
                                        \App\Enums\SubscriptionStatus::PastDue => 'red',
                                        \App\Enums\SubscriptionStatus::Cancelled => 'zinc',
                                        default => 'zinc',
                                    }">
                                    {{ $this->statusLabel($tenant->subscription?->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $tenant->subscription?->current_period_end?->format('d/m/Y') ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell align="end">

                                <flux:dropdown>

                                    <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                    <flux:menu class="min-w-0!">
                                        <flux:menu.item icon="pencil" title="Modifier" wire:click="startEditing('{{ $tenant->id }}')"/>

                                    @if ($tenant->subscription && $tenant->subscription->status !== \App\Enums\SubscriptionStatus::Cancelled)
                                        
                                        <flux:menu.item :icon="$tenant->subscription && $tenant->subscription->status !== \App\Enums\SubscriptionStatus::Cancelled ? 'puzzle-piece' : 'eye'" :title="$tenant->subscription && $tenant->subscription->status !== \App\Enums\SubscriptionStatus::Cancelled ? 'Gérer les add-ons' : 'Voir'" wire:click="showAddons('{{ $tenant->id }}')"/>    

                                        <flux:menu.item :icon="$tenant->subscription && $tenant->subscription->status !== \App\Enums\SubscriptionStatus::Cancelled ? 'banknotes' : 'eye'" :title="$tenant->subscription && $tenant->subscription->status !== \App\Enums\SubscriptionStatus::Cancelled ? 'Voir les factures' : 'Voir'" wire:click="showInvoices('{{ $tenant->id }}')"/>   
                                    @endif 
                                    </flux:menu>

                                </flux:dropdown>

                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="edit-subscription" class="md:w-[28rem]">
        <flux:heading size="lg">Plan &amp; abonnement</flux:heading>
        <flux:subheading>Nouvel abonnement = essai gratuit 14 jours sans carte</flux:subheading>

        <form wire:submit="saveSubscription" class="flex flex-col gap-4">
            <flux:select wire:model="planCode" :label="__('Plan')">
                @foreach ($this->plans as $plan)
                    <flux:select.option :value="$plan->code">
                        {{ $plan->name }} — {{ number_format($plan->monthly_price, 0, ',', ' ') }} FCFA/mois
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="billingPeriod" :label="__('Période de facturation')">
                <flux:select.option value="monthly">{{ __('Mensuel') }}</flux:select.option>
                <flux:select.option value="annual">{{ __('Annuel (−20 %)') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model="couponId" :label="__('Coupon (optionnel)')">
                <flux:select.option value="">{{ __('Aucun') }}</flux:select.option>
                @foreach ($this->validCoupons as $coupon)
                    <flux:select.option :value="$coupon->id">{{ $coupon->code }} — {{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : number_format($coupon->discount_value / 100, 2).' €' }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        {{ __('Annuler') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Enregistrer') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="tenant-addons" class="md:w-[28rem]">
        <flux:heading size="lg">Add-ons</flux:heading>
        <flux:subheading>Facturation au prorata de la période en cours</flux:subheading>

        <form wire:submit="saveAddons" class="flex flex-col gap-4">
            @foreach ($this->addons as $addon)
                <flux:input
                    type="number"
                    min="0"
                    max="100"
                    wire:model="addonQuantities.{{ $addon->id }}"
                    :label="$addon->name . ' — ' . number_format($addon->monthly_price, 0, ',', ' ') . ' FCFA/' . ($addon->unit ?? 'mois')"
                />
            @endforeach

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        {{ __('Annuler') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Enregistrer') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="tenant-invoices" class="md:w-[36rem]">
        <flux:heading size="lg">Factures</flux:heading>

        <div class="mt-4 space-y-3">
            @forelse ($this->invoices as $invoice)
                <div class="rounded-lg border border-zinc-200 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <flux:text class="font-medium">{{ $invoice->number }}</flux:text>
                            <flux:text class="block text-xs text-zinc-500">
                                {{ $invoice->created_at->format('d/m/Y') }} · due le {{ $invoice->due_at?->format('d/m/Y') ?? '—' }}
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge
                                size="sm"
                                :color="match ($invoice->status) {
                                    \App\Enums\InvoiceStatus::Pending => 'amber',
                                    \App\Enums\InvoiceStatus::Paid => 'green',
                                    \App\Enums\InvoiceStatus::Failed => 'red',
                                    \App\Enums\InvoiceStatus::Overdue => 'red',
                                    \App\Enums\InvoiceStatus::Void => 'zinc',
                                }">
                                {{ $this->invoiceStatusLabel($invoice->status) }}
                            </flux:badge>
                            <flux:text class="font-semibold tabular-nums">
                                {{ number_format($invoice->amount, 0, ',', ' ') }} FCFA
                            </flux:text>
                        </div>
                    </div>

                    @if ($invoice->payment_link_url && in_array($invoice->status, [\App\Enums\InvoiceStatus::Pending, \App\Enums\InvoiceStatus::Overdue], true))
                        <div class="mt-2 flex items-center justify-between gap-3 border-t border-dashed border-zinc-200 pt-2">
                            <flux:text class="truncate font-mono text-xs text-zinc-500">
                                {{ $invoice->payment_link_url }}
                            </flux:text>
                            <div class="flex shrink-0 items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="arrow-top-right-on-square"
                                    :tooltip="__('Ouvrir le lien')" :href="$invoice->payment_link_url" target="_blank" />
                                <flux:button variant="subtle" size="xs" icon="check-circle"
                                    :tooltip="__('Simuler le paiement (sandbox)')"
                                    wire:click="simulatePayment({{ $invoice->id }})"
                                    wire:confirm="Confirmer le paiement de cette facture ?" />
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">Aucune facture pour le moment.</flux:text>
            @endforelse
        </div>
    </flux:modal>
</div>
