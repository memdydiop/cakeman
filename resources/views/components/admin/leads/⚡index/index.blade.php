<div>
    <x-page-header
        title="Leads"
        subtitle="Prospects de la landing — acquisition et conversion"
        :breadcrumbs="[['label' => 'Leads']]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-widget icon="megaphone" color="blue" title="Total" :value="$this->stats['total']" :trend-text="'depuis le lancement'" trend-color="text-blue-600" />
        <x-widget icon="sparkles" color="blue" title="Nouveaux" :value="$this->stats['new']" :trend-text="'à traiter'" trend-color="text-blue-600" />
        <x-widget icon="phone" color="amber" title="Contactés" :value="$this->stats['contacted']" :trend-text="'en cours de suivi'" trend-color="text-amber-600" />
        <x-widget icon="check-circle" color="green" title="Convertis" :value="$this->stats['converted']" :trend-text="'boutiques créées'" trend-color="text-green-600" />
        <x-widget icon="chart-pie" color="indigo" title="Taux de conversion" :value="$this->stats['conversion_rate'] . '%'" :trend-text="'des leads'" trend-color="text-indigo-600" />
    </div>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="magnifying-glass" title="Leads" subtitle="Filtres, recherche et export" />

        <x-card-body>
            <div class="flex flex-wrap items-center gap-3">
                <flux:input wire:model.live.debounce="search" placeholder="Nom, e-mail, téléphone ou boutique…" class="w-72" icon="magnifying-glass" />
                <flux:select wire:model.live="filters.status" class="w-44">
                    <option value="all">Tous les statuts</option>
                    @foreach (\App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" icon="arrow-down-tray" wire:click="export" class="ms-auto">
                    {{ __('Exporter (CSV)') }}
                </flux:button>
            </div>
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 mt-4">
        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-10"></flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Prospect</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Téléphone</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Boutique</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Source</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Reçu le</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->leads as $lead)
                        <flux:table.row :key="$lead->id" class="hover:bg-zinc-50/80">
                            <flux:table.cell class="w-10">
                                <div class="flex size-9 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">
                                    {{ \Illuminate\Support\Str::initials($lead->name) }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium">{{ $lead->name }}</div>
                                <div class="text-sm text-zinc-500">{{ $lead->email }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="whitespace-nowrap font-mono text-sm tabular-nums">{{ $lead->phone ?? '—' }}</span>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $lead->shop_name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $lead->source }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="whitespace-nowrap text-sm tabular-nums">{{ $lead->created_at?->format('d/m/Y H:i') }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$this->statusColor($lead->status)">
                                    {{ $this->statusLabel($lead->status) }}
                                </flux:badge>
                                @if ($lead->convertedTenant)
                                    <flux:text class="block text-xs text-zinc-500">→ {{ $lead->convertedTenant->name }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($lead->status !== \App\Enums\LeadStatus::Converted)
                                    <flux:dropdown>
                                        <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                        <flux:menu>
                                            @if ($lead->status === \App\Enums\LeadStatus::New)
                                                <flux:menu.item icon="phone" title="Marquer contacté" wire:click="markContacted({{ $lead->id }})" />
                                            @endif
                                            <flux:menu.item icon="check-circle" title="Convertir (boutique créée)" wire:click="requestConversion({{ $lead->id }})" />
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-zinc-500">
                                {{ __('Aucun lead.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $this->leads->links() }}
            </div>
        </x-card-body>
    </flux:card>

    <flux:modal name="lead-convert" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading>Associer une boutique</flux:heading>
                <flux:subheading>Choisissez la boutique créée à partir de ce lead (facultatif).</flux:subheading>
            </div>

            <flux:select wire:model="convertTenantId" placeholder="Aucune boutique (seulement le statut)">
                <option value="">Aucune boutique</option>
                @foreach ($this->tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Annuler') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" wire:click="confirmConversion">
                    {{ __('Confirmer la conversion') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>