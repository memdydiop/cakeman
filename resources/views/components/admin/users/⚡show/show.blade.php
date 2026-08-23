<div>
    <x-page-header
        :title="$this->user->name"
        :subtitle="$this->user->email"
        :breadcrumbs="[
            [
                'label' => 'Utilisateurs',
                'href' => route('admin.users.index'),
                'wire_navigate' => true,
            ],
            [
                'label' => $this->user->name,
            ],
        ]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="shield-check" :color="$this->user->is_active ? 'green' : 'zinc'" title="Statut"
            :value="$this->user->is_active ? 'Actif' : 'Désactivé'"
            :trend-text="'accès console'" :trend-color="$this->user->is_active ? 'text-green-600' : 'text-zinc-500'" />

        <x-widget icon="clock" color="blue" title="Dernière connexion"
            :value="$this->user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais'"
            :trend-text="'sur la console'" trend-color="text-blue-600" />

        <x-widget icon="finger-print" :color="$this->user->two_factor_confirmed_at !== null ? 'indigo' : 'zinc'" title="Double authentification"
            :value="$this->user->two_factor_confirmed_at !== null ? 'Activée' : 'Non activée'"
            :trend-text="'TOTP + passkeys'" trend-color="text-indigo-600" />

        <x-widget icon="key" color="amber" title="Rôles"
            :value="$this->user->roles->pluck('name')->join(', ') ?: '—'"
            :trend-text="count($this->user->passkeys).' passkey(s)'" trend-color="text-amber-600" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3 mt-4">
        <flux:card class="p-6 lg:col-span-2">
            <x-card-header icon="information-circle" title="Informations"
                :actions="[
                    [
                        'label' => $this->user->is_active ? 'Désactiver' : 'Réactiver',
                        'icon' => $this->user->is_active ? 'pause' : 'play',
                        'wire:click' => 'toggleActive',
                        'wire:confirm' => $this->user->is_active
                            ? 'Désactiver ce compte ? Ses sessions actives seront révoquées immédiatement.'
                            : 'Réactiver ce compte ?',
                        'show' => $this->user->id !== auth('admin')->id(),
                    ],
                    [
                        'label' => 'Réinitialiser le mot de passe',
                        'icon' => 'key',
                        'wire:click' => 'sendPasswordReset',
                        'wire:confirm' => 'Envoyer un lien de réinitialisation par e-mail ?',
                    ],
                    [
                        'label' => 'Supprimer',
                        'icon' => 'trash',
                        'wire:click' => 'deleteUser',
                        'wire:confirm' => 'Supprimer cet utilisateur ? Cette action est irréversible.',
                        'show' => ! $this->user->is_active && $this->user->id !== auth('admin')->id(),
                    ],
                    [
                        'label' => 'Export RGPD',
                        'icon' => 'arrow-down-tray',
                        'wire:click' => 'exportGdpr',
                    ],
                ]" />

            <x-card-body>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:subheading>Nom</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->name }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>E-mail</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->email }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Téléphone</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->phone ?? '—' }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Statut</flux:subheading>
                        <flux:badge :color="$this->user->is_active ? 'green' : 'zinc'">
                            {{ $this->user->is_active ? 'Actif' : 'Désactivé' }}
                        </flux:badge>
                    </div>

                    <div>
                        <flux:subheading>Créé le</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->created_at?->format('d/m/Y H:i') }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>Dernière connexion</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</flux:text>
                    </div>

                    <div>
                        <flux:subheading>E-mail vérifié</flux:subheading>
                        <flux:badge :color="$this->user->email_verified_at !== null ? 'green' : 'amber'">
                            {{ $this->user->email_verified_at !== null ? 'Oui' : 'Non' }}
                        </flux:badge>
                    </div>

                    <div>
                        <flux:subheading>Passkeys</flux:subheading>
                        <flux:text class="font-medium">{{ $this->user->passkeys->count() }} enregistrée(s)</flux:text>
                    </div>
                </dl>

                @if ($this->pendingInvitation)
                    <flux:callout color="amber" icon="paper-airplane" class="mt-4">
                        <flux:callout.text>
                            {{ __('Invitation en attente pour cet utilisateur — lien valable jusqu\'au :date.', [
                                'date' => $this->pendingInvitation->expires_at->format('d/m/Y H:i'),
                            ]) }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                @error('user')
                    <flux:callout color="red" icon="x-circle" class="mt-4">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
            </x-card-body>
        </flux:card>

        <flux:card class="p-6">
            <x-card-header icon="users" title="Rôles et permissions" subtitle="RBAC console (CdC § 2.3)" />
            <x-card-body>
                <flux:subheading class="mb-1!">Rôles</flux:subheading>
                <div class="flex flex-wrap gap-1.5">
                    @forelse ($this->user->roles as $role)
                        <flux:badge color="blue">{{ $role->name }}</flux:badge>
                    @empty
                        <flux:text class="text-sm text-zinc-500">—</flux:text>
                    @endforelse
                </div>

                <flux:subheading class="mt-4 mb-1!">Permissions directes</flux:subheading>
                @forelse ($this->user->permissions as $permission)
                    <flux:badge color="zinc" size="sm" class="mr-1! mb-1!">{{ $permission->name }}</flux:badge>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('Aucune permission directe (héritées des rôles).') }}</flux:text>
                @endforelse
            </x-card-body>
        </flux:card>
    </div>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="globe-alt" title="Sessions actives" subtitle="Sessions de la console en cours (20 plus récentes)" />
        <x-card-body>
            @if (count($this->sessions) === 0)
                <flux:text class="text-sm text-zinc-500">{{ __('Aucune session active.') }}</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Adresse IP</flux:table.column>
                        <flux:table.column>Navigateur</flux:table.column>
                        <flux:table.column>Dernière activité</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->sessions as $session)
                            <flux:table.row :key="$session['id']">
                                <flux:table.cell class="font-mono text-sm">{{ $session['ip_address'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="max-w-xs truncate">{{ $session['user_agent'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $session['last_activity']->diffForHumans() }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="arrow-right-start-on-rectangle" title="Connexions récentes" subtitle="10 dernières tentatives (succès / échec, IP, 2FA)" />
        <x-card-body>
            @forelse ($this->recentLogins as $login)
                <div class="flex items-center justify-between gap-4 py-1.5 border-b border-dashed border-zinc-200 dark:border-zinc-800 last:border-0">
                    <flux:badge :color="$login->success ? 'green' : 'red'" size="sm">
                        {{ $login->success ? 'Réussie' : 'Échouée' }}
                    </flux:badge>
                    <flux:text class="text-sm font-mono">{{ $login->ip_address ?? '—' }}</flux:text>
                    @if ($login->two_factor)
                        <flux:badge color="blue" size="sm">2FA</flux:badge>
                    @endif
                    <flux:text class="text-xs text-zinc-400 shrink-0">{{ $login->created_at->format('d/m/Y H:i:s') }}</flux:text>
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">{{ __('Aucune tentative enregistrée.') }}</flux:text>
            @endforelse
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="shield-check" title="Journal d'audit" subtitle="Actions réalisées par cet utilisateur ou le concernant" />
        <x-card-body>
            @forelse ($this->auditLogs as $log)
                <div class="flex items-start justify-between gap-4 py-2 border-b border-dashed border-zinc-200 dark:border-zinc-800 last:border-0">
                    <div class="min-w-0">
                        <flux:text class="font-medium text-sm">{{ $this->actionLabel($log->action) }}</flux:text>
                        @if (! empty($log->properties))
                            <flux:text class="text-xs text-zinc-500 block truncate">{{ $this->details($log->properties) }}</flux:text>
                        @endif
                    </div>
                    <flux:text class="text-xs text-zinc-400 shrink-0">{{ $log->created_at?->format('d/m/Y H:i') }}</flux:text>
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">{{ __('Aucun événement d\'audit pour cet utilisateur.') }}</flux:text>
            @endforelse

            <div class="mt-4">{{ $this->auditLogs->links() }}</div>
        </x-card-body>
    </flux:card>
</div>