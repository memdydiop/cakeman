<div>
    <x-page-header 
        :title="$this->tenant->name" 
        :subtitle="$this->tenant->domains->first()?->domain" 
        :breadcrumbs="[
            [
                'label' => 'Boutiques',
                'href' => route('admin.tenants.index'),
                'wire_navigate' => true,
            ],
            [
                'label' => $this->tenant->name,
            ],
        ]" />

    @php
        $limits = $this->tenant->planLimits();
        $statusTint = match ($this->tenant->status) {
            \App\Enums\TenantStatus::Active => 'green',
            \App\Enums\TenantStatus::Pending => 'blue',
            \App\Enums\TenantStatus::Suspended => 'red',
            \App\Enums\TenantStatus::Trialing => 'amber',
            \App\Enums\TenantStatus::Cancelled => 'zinc',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="shield-check" :color="$statusTint" title="Statut"
            :value="match ($this->tenant->status) {
                \App\Enums\TenantStatus::Active => 'Active',
                \App\Enums\TenantStatus::Pending => 'En attente',
                \App\Enums\TenantStatus::Suspended => 'Suspendue',
                \App\Enums\TenantStatus::Trialing => 'Essai',
                \App\Enums\TenantStatus::Cancelled => 'Annulée',
            }"
            :trend-text="$this->tenant->isUnderMaintenance() ? 'en maintenance' : 'opérationnelle'"
            :trend-color="match ($this->tenant->status) {
                \App\Enums\TenantStatus::Active => 'text-green-600',
                \App\Enums\TenantStatus::Pending => 'text-blue-600',
                \App\Enums\TenantStatus::Suspended => 'text-red-600',
                \App\Enums\TenantStatus::Trialing => 'text-amber-600',
                \App\Enums\TenantStatus::Cancelled => 'text-zinc-500',
            }" />

        <x-widget icon="credit-card" color="indigo" title="Plan"
            :value="$this->tenant->subscription?->plan->name ?? 'Gratuit'"
            :trend-text="match ($this->tenant->subscription?->status) {
                \App\Enums\SubscriptionStatus::Active => 'abonnement actif',
                \App\Enums\SubscriptionStatus::Trialing => 'essai en cours',
                \App\Enums\SubscriptionStatus::PastDue => 'paiement en retard',
                \App\Enums\SubscriptionStatus::Cancelled => 'abonnement annulé',
                default => 'sans abonnement',
            }" trend-color="text-indigo-600" />

        <x-widget icon="users" color="blue" title="Utilisateurs" :value="$limits['users'] ?? 'Illimité'"
            :trend-text="'limite du plan'" trend-color="text-blue-600" />

        <x-widget icon="shopping-cart" color="amber" title="Commandes / mois" :value="$limits['orders_per_month'] ?? 'Illimité'"
            :trend-text="'limite du plan'" trend-color="text-amber-600" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3 mt-4">
        <flux:card class="p-6 lg:col-span-2">
            <x-card-header icon="information-circle" title="Informations"
                :actions="[
                    [
                        'label' => 'Inviter Gérant',
                        'icon' => 'user-plus',
                        'x-on:click' => '$flux.modal(\'invite-manager\').show()',
                        'show' => $this->tenant->status === \App\Enums\TenantStatus::Pending,
                    ],
                    [
                        'label' => 'Remplacer le gérant',
                        'icon' => 'arrows-right-left',
                        'x-on:click' => '$flux.modal(\'replace-manager\').show()',
                        'show' => $this->tenant->status !== \App\Enums\TenantStatus::Pending
                            && $this->tenant->status !== \App\Enums\TenantStatus::Cancelled
                            && $this->hasValidManager,
                    ],
                    [
                        'label' => 'Prolonger l\'essai',
                        'icon' => 'calendar-days',
                        'wire:click' => 'extendTrial',
                        'wire:confirm' => 'Prolonger la période d\'essai de 14 jours ?',
                        'show' => $this->tenant->subscription?->status === \App\Enums\SubscriptionStatus::Trialing,
                    ],
                    [
                        'label' => $this->tenant->status === \App\Enums\TenantStatus::Active ? 'Suspendre' : 'Réactiver',
                        'icon' => $this->tenant->status === \App\Enums\TenantStatus::Active ? 'pause' : 'play',
                        'wire:click' => 'toggleStatus',
                        'wire:confirm' => $this->tenant->status === \App\Enums\TenantStatus::Active
                            ? 'Suspendre cette boutique ? Ses accès seront immédiatement bloqués.'
                            : 'Réactiver cette boutique ?',
                        'show' => $this->tenant->status !== \App\Enums\TenantStatus::Cancelled
                            && ($this->tenant->status === \App\Enums\TenantStatus::Active || $this->hasValidManager),
                    ],
                    [
                        'label' => 'Archiver',
                        'icon' => 'archive-box',
                        'wire:click' => 'cancelTenant',
                        'wire:confirm' => 'Annuler et archiver cette boutique ? Ses accès seront bloqués ; elle pourra être supprimée définitivement ensuite.',
                        'show' => $this->tenant->status !== \App\Enums\TenantStatus::Cancelled,
                    ],
                    [
                        'label' => 'Suprimer',
                        'icon' => 'trash',
                        'wire_navigate' => true,
                        'wire:click' => 'deleteTenant',
                        'wire:confirm' => 'Supprimer définitivement cette boutique ? Sa base de données et toutes ses données seront supprimées.',
                        'show' => $this->tenant->status === \App\Enums\TenantStatus::Cancelled,
                    ],
                    
                ]" />

            <x-card-body>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:subheading>Domaine</flux:subheading>
                    <flux:text class="font-medium">{{ $this->tenant->domains->first()?->domain ?? '—' }}</flux:text>
                </div>

                <div>
                    <flux:subheading>Statut</flux:subheading>
                    <flux:badge
                        :color="match ($this->tenant->status) {
                                                                            \App\Enums\TenantStatus::Active => 'green',
                                                                            \App\Enums\TenantStatus::Pending => 'blue',
                                                                            \App\Enums\TenantStatus::Suspended => 'red',
                                                                            \App\Enums\TenantStatus::Trialing => 'amber',
                                                                            \App\Enums\TenantStatus::Cancelled => 'zinc',
                                                                        }">
                        {{ match ($this->tenant->status) {
                            \App\Enums\TenantStatus::Active => 'Active',
                            \App\Enums\TenantStatus::Pending => 'En attente',
                            \App\Enums\TenantStatus::Suspended => 'Suspendue',
                            \App\Enums\TenantStatus::Trialing => 'Essai',
                            \App\Enums\TenantStatus::Cancelled => 'Annulée',
                        } }}
                    </flux:badge>
                </div>

                <div>
                    <flux:subheading>Identifiant tenant</flux:subheading>
                    <flux:text class="font-mono text-sm">{{ $this->tenant->id }}</flux:text>
                </div>

                <div>
                    <flux:subheading>Créée le</flux:subheading>
                    <flux:text class="font-medium">{{ $this->tenant->created_at?->format('d/m/Y H:i') }}</flux:text>
                </div>
            </dl>
            </x-card-body>
        </flux:card>

        <flux:card class="p-6">
            <x-card-header icon="credit-card" title="Abonnement" subtitle="Plan et limites (Module 12)" />
            <x-card-body>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:subheading>Plan</flux:subheading>
                        <flux:text class="font-medium">{{ $this->tenant->subscription?->plan->name ?? 'Gratuit' }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Utilisateurs</flux:subheading>
                        <flux:text class="font-medium">{{ $limits['users'] ?? 'Illimité' }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Commandes / mois</flux:subheading>
                        <flux:text class="font-medium">{{ $limits['orders_per_month'] ?? 'Illimité' }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Stockage</flux:subheading>
                        <flux:text class="font-medium">{{ isset($limits['storage_gb']) ? $limits['storage_gb'].' Go' : 'Illimité' }}</flux:text>
                    </div>
                </dl>

                @if (auth('admin')->user()?->hasPermissionTo(\App\Enums\AdminPermission::BillingManage->value, 'admin'))
                    <flux:button variant="ghost" size="sm" :href="route('admin.subscriptions.index')" wire:navigate class="mt-4">
                        {{ __('Gérer l\'abonnement') }}
                    </flux:button>
                @endif
            </x-card-body>
        </flux:card>

    <flux:card class="p-6 lg:col-span-3">
            <x-card-header icon="wrench-screwdriver" title="Opérations" subtitle="Maintenance, cache et données (CdC § 12)" />

            <x-card-body>
                <div class="flex flex-wrap gap-2">
                    @if ($this->canToggleMaintenance)
                        <flux:button variant="ghost" size="sm" icon="shield-check" wire:click="toggleMaintenance"
                            :wire:confirm="$this->tenant->isUnderMaintenance()
                                ? 'Lever la maintenance de cette boutique ?'
                                : 'Activer la maintenance pour 2 heures ? Ses accès web seront bloqués (503).'">
                            {{ $this->tenant->isUnderMaintenance() ? __('Lever la maintenance') : __('Activer la maintenance') }}
                        </flux:button>
                    @endif

                    @if ($this->canManage)
                        <flux:button variant="ghost" size="sm" icon="sparkles" wire:click="purgeCache"
                            wire:confirm="Purger le cache de cette boutique ?">
                            {{ __('Purger le cache') }}
                        </flux:button>

                        <flux:button variant="ghost" size="sm" icon="arrow-down-tray" wire:click="exportData"
                            wire:confirm="Exporter les données de cette boutique (JSON) ?">
                            {{ __('Exporter les données') }}
                        </flux:button>

                        <flux:button variant="subtle" size="sm" icon="user-circle" wire:click="impersonate"
                            wire:confirm="Se connecter en tant que gérant de cette boutique ?">
                            {{ __('Impersonate') }}
                        </flux:button>
                    @endif
                </div>

                @if ($this->tenant->isUnderMaintenance())
                    <flux:badge color="red" class="mt-3">
                        {{ __('Maintenance jusqu\'à :heure', ['heure' => $this->tenant->maintenance_until->format('d/m/Y H:i')]) }}
                    </flux:badge>
                @endif
            </x-card-body>
        </flux:card>

    <flux:card class="p-6 lg:col-span-3">
        <x-card-header icon="globe-alt" title="Domaines" subtitle="Sous-domaines de la boutique (le dernier ne peut pas être retiré)" />

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Domaine</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Accès</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->tenant->domains as $domain)
                        <flux:table.row :key="$domain->domain" class="hover:bg-zinc-50/80">
                            <flux:table.cell class="font-medium">{{ $domain->domain }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="green">{{ __('Actif') }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($this->canManage && $this->tenant->domains->count() > 1)
                                    <flux:button variant="subtle" size="xs" icon="x-circle" title="Retirer"
                                        wire:click="removeDomain('{{ $domain->domain }}')"
                                        wire:confirm="Retirer le domaine {{ $domain->domain }} ? Les accès web de la boutique ne passeront plus par lui." />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-500">
                                {{ __('Aucun domaine pour cette boutique.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($this->canManage)
                <form wire:submit="addDomain" class="mt-4 flex items-start gap-2">
                    <flux:input wire:model="newDomain" placeholder="sous-domaine (ex. patisserie-a)…" class="flex-1" />
                    <flux:button variant="primary" type="submit" icon="plus">{{ __('Ajouter') }}</flux:button>
                </form>
                <flux:error name="newDomain" class="mt-2" />
            @endif
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 lg:col-span-3">

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Gérant</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Envoyée le</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Expire le</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->invitations as $invitation)
                        <flux:table.row :key="$invitation['id']" class="hover:bg-zinc-50/80">
                            <flux:table.cell>
                                <div class="font-medium">{{ $invitation['name'] }}</div>
                                <div class="text-sm text-zinc-500">{{ $invitation['email'] }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $invitation['created_at'] }}</flux:table.cell>
                            <flux:table.cell>{{ $invitation['accepted_at'] !== null ? '—' : $invitation['expires_at'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($invitation['accepted_at'] !== null)
                                    <flux:badge size="sm" color="zinc">
                                        {{ __('Consommée le :date', ['date' => $invitation['accepted_at']]) }}
                                    </flux:badge>
                                @elseif ($invitation['is_valid'])
                                    <flux:badge size="sm" color="green">{{ __('En attente') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">{{ __('Expirée') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($invitation['is_pending'] && $this->canManage)
                                    <flux:dropdown>
                                        <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                        <flux:menu>
                                            <flux:menu.item icon="paper-airplane" title="Renvoyer" wire:click="resendInvitation({{ $invitation['id'] }})" />
                                            <flux:menu.item icon="x-circle" title="Révoquer" wire:click="revokeInvitation({{ $invitation['id'] }})"
                                                wire:confirm="Révoquer cette invitation ? Le compte inactif associé sera supprimé." />
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">
                                {{ __('Aucune invitation envoyée.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>
    </div>

    <flux:modal name="invite-manager" class="md:w-[28rem]">
        <flux:heading size="lg">Inviter un gérant</flux:heading>
        <flux:subheading>Le gérant recevra un lien d'activation valable 48 heures</flux:subheading>

        <form wire:submit="inviteManager" class="flex flex-col gap-4">
            <flux:input
                wire:model="inviteManagerName"
                :label="__('Nom du gérant')"
                type="text"
                required
                autofocus
                placeholder="Aya Kouassi"
            />

            <flux:input
                wire:model="inviteManagerEmail"
                :label="__('E-mail du gérant')"
                type="email"
                required
                placeholder="aya@douceur.ci"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        {{ __('Annuler') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Envoyer l\'invitation') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @if ($this->tenant->status !== \App\Enums\TenantStatus::Pending
        && $this->tenant->status !== \App\Enums\TenantStatus::Cancelled
        && $this->hasValidManager)
        <flux:modal name="replace-manager" class="md:w-[28rem]">
            <flux:heading size="lg">Remplacer le gérant</flux:heading>
            <flux:subheading>Le gérant actuel reste actif jusqu'à l'acceptation du nouveau, puis il est désactivé.</flux:subheading>

            <form wire:submit="replaceManager" class="flex flex-col gap-4">
                <flux:input
                    wire:model="inviteManagerName"
                    :label="__('Nom du nouveau gérant')"
                    type="text"
                    required
                    autofocus
                    placeholder="Aya Kouassi"
                />

                <flux:input
                    wire:model="inviteManagerEmail"
                    :label="__('E-mail du nouveau gérant')"
                    type="email"
                    required
                    placeholder="aya@douceur.ci"
                />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">
                            {{ __('Annuler') }}
                        </flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">
                        {{ __('Envoyer l\'invitation de remplacement') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
