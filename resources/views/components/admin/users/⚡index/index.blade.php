<div class="space-y-6">
    <x-page-header title="Utilisateurs" subtitle="Utilisateurs Admin" :breadcrumbs="[
        [
            'label' => __('Utilisateurs'),
        ],
    ]" />

    <flux:card class="p-6">
        <x-card-header icon="users" title="Utilisateurs" subtitle="Utilisateurs de la console" :actions="[
            [
                'label' => 'Inviter',
                'icon' => 'plus',
                'x-on:click' => '$flux.modal(\'invite-user\').show()',
            ],
        ]" />

        <x-card-body class="">
            <x-table-toolbar search-placeholder="Rechercher un utilisateur…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.role" size="sm" class="w-40">
                        <flux:select.option value="all">{{ __('Tous les rôles') }}</flux:select.option>
                        @foreach ($this->roleOptions() as $role)
                            <flux:select.option :value="$role">{{ $role }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filters.status" size="sm" class="w-36">
                        <flux:select.option value="all">{{ __('Tous les statuts') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Actif') }}</flux:select.option>
                        <flux:select.option value="inactive">{{ __('Désactivé') }}</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="filters.two_factor" size="sm" class="w-36">
                        <flux:select.option value="all">{{ __('2FA : tous') }}</flux:select.option>
                        <flux:select.option value="enabled">{{ __('2FA activée') }}</flux:select.option>
                        <flux:select.option value="disabled">{{ __('2FA désactivée') }}</flux:select.option>
                    </flux:select>
                </x-slot>

                <x-slot name="actions">
                    <flux:button variant="subtle" size="sm" icon="arrow-down-tray" wire:click="exportCsv">
                        {{ __('Exporter CSV') }}
                    </flux:button>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->adminUsers">
                <flux:table.columns>
                    <flux:table.column>Nom</flux:table.column>
                    <flux:table.column>E-mail</flux:table.column>
                    <flux:table.column>Rôle</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>2FA</flux:table.column>
                    <flux:table.column>Dernière connexion</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->adminUsers as $admin)
                        <flux:table.row :key="$admin->id">
                            <flux:table.cell class="font-medium">
                                <flux:link variant="ghost" :href="route('admin.users.show', $admin)" wire:navigate>{{ $admin->name }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $admin->email }}</flux:table.cell>
                            <flux:table.cell>{{ $admin->roles->pluck('name')->join(', ') ?: '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$admin->is_active ? 'green' : 'zinc'">
                                    {{ $admin->is_active ? 'Actif' : 'Désactivé' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($admin->two_factor_confirmed_at !== null)
                                    <flux:badge color="blue">{{ __('Activée') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc">{{ __('Non') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $admin->last_login_at?->format('d/m/Y H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown>

                                    <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item icon="eye" title="Voir"
                                            :href="route('admin.users.show', $admin)" wire:navigate />
                                        <flux:menu.item icon="pencil" title="Modifier"
                                            wire:click="startEditing({{ $admin->id }})" />

                                        @unless ($admin->is_active)
                                            <flux:separator />
                                            <flux:menu.item icon="trash" title="Supprimer"
                                                wire:click="deleteUser({{ $admin->id }})"
                                                wire:confirm="Supprimer cet utilisateur ? Cette action est irréversible."
                                                variant="danger" />
                                        @endunless
                                    </flux:menu>

                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:card class="p-6">
        <x-card-header icon="paper-airplane" title="Invitations"
            subtitle="Liens d'activation envoyés par e-mail (consommées et en attente)" />

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>E-mail</flux:table.column>
                    <flux:table.column>Rôle</flux:table.column>
                    <flux:table.column>Envoyée le</flux:table.column>
                    <flux:table.column>Expire le</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->invitations as $invitation)
                        <flux:table.row :key="$invitation->id">
                            <flux:table.cell class="font-medium">{{ $invitation->email }}</flux:table.cell>
                            <flux:table.cell>{{ $invitation->role }}</flux:table.cell>
                            <flux:table.cell>{{ $invitation->created_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                {{ $invitation->accepted_at !== null ? '—' : $invitation->expires_at->format('d/m/Y H:i') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($invitation->accepted_at !== null)
                                    <flux:badge size="sm" color="zinc">
                                        {{ __('Consommée le :date', ['date' => $invitation->accepted_at->format('d/m/Y H:i')]) }}
                                    </flux:badge>
                                @elseif ($invitation->isValid())
                                    <flux:badge size="sm" color="green">{{ __('Valide') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">{{ __('Expirée') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($invitation->accepted_at === null)
                                    <flux:dropdown>
                                        <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item icon="paper-airplane" title="Renvoyer"
                                                wire:click="resendInvitation({{ $invitation->id }})" />
                                            <flux:menu.item icon="x-circle" title="Révoquer"
                                                wire:click="revokeInvitation({{ $invitation->id }})"
                                                wire:confirm="Révoquer cette invitation ? Le compte inactif associé sera supprimé." />
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">
                                {{ __('Aucune invitation envoyée.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="invite-user" class="md:w-[28rem]">
        <flux:heading size="lg">Inviter un utilisateur</flux:heading>
        <flux:subheading>Le lien d'activation sera valable 48 heures</flux:subheading>

        <form wire:submit="invite" class="flex flex-col gap-4">
            <flux:input wire:model="inviteEmail" :label="__('E-mail')" type="email" required autofocus
                placeholder="admin@cakeman.test" />

            <flux:select wire:model="inviteRole" :label="__('Rôle')">
                @foreach ($this->roleOptions() as $role)
                    <flux:select.option :value="$role">{{ $role }}</flux:select.option>
                @endforeach
            </flux:select>

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

    <flux:modal name="edit-user" class="md:w-[32rem]">
        <flux:heading size="lg">Modifier l'utilisateur</flux:heading>

        <form wire:submit="saveEditing" class="flex flex-col gap-4">
            <div>
                <flux:heading size="sm" class="mb-2!">{{ __('Rôles') }}</flux:heading>
                <div class="space-y-1">
                    @foreach ($this->roleOptions() as $role)
                        <flux:checkbox wire:model="editRoles" :value="$role" :label="$role" />
                    @endforeach
                </div>
            </div>

            <div>
                <flux:heading size="sm" class="mb-2!">{{ __('Permissions directes') }}</flux:heading>
                @foreach ($this->permissionGroups() as $group => $permissions)
                    <div class="mt-2">
                        <flux:heading size="sm" class="mb-1! text-xs! text-zinc-500!">{{ $group }}
                        </flux:heading>
                        <div class="space-y-1">
                            @foreach ($permissions as $permission)
                                <flux:checkbox wire:model="editPermissions" :value="$permission->value"
                                    :label="$this->permissionLabel($permission)" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:switch wire:model="editIsActive" :label="__('Compte actif')"
                description="La désactivation révoque immédiatement les sessions" />

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
</div>
