<div class="space-y-6">
    <x-page-header title="Rôles" subtitle="Gestion des rôles et de leurs permissions" />

    <flux:card class="p-6">
        <x-card-header
        icon="shield-check" title="Rôles de la console" subtitle="Permissions par rôle"
        :actions="[
            [
                'label' => 'Nouveau rôle',
                'icon' => 'plus',
                'x-on:click' => '$flux.modal(\'create-role\').show()',
            ],
        ]" />

        <x-card-body>

            <x-table-toolbar 
                search-placeholder="Rechercher un rôle..." 
                :per-page="'perPage'"/>


            <flux:table :paginate="$this->roles">
                <flux:table.columns>
                    <flux:table.column>Rôle</flux:table.column>
                    <flux:table.column>Utilisateurs</flux:table.column>
                    <flux:table.column>Permissions</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->roles as $role)
                        <flux:table.row :key="$role->id">
                            <flux:table.cell class="font-medium">{{ $role->name }}</flux:table.cell>
                            <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="indigo">{{ $role->permissions_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown>

                                    <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil" title="Modifier" wire:click="startEditing({{ $role->id }})" />

                                        @if ($role->name !== \Database\Seeders\AdminRolesSeeder::ROLE_SUPER_ADMIN)
                                            <flux:separator />

                                            <flux:menu.item icon="trash" title="Supprimer" wire:click="deleteRole({{ $role->id }})"
                                                wire:confirm="Supprimer ce rôle ? Les utilisateurs perdront ces permissions."
                                                variant="danger" />
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

    <flux:modal name="create-role" class="md:w-[32rem]">
        <flux:heading size="lg">Nouveau rôle</flux:heading>
        <flux:subheading>Choisissez un nom et les permissions du rôle</flux:subheading>

        <form wire:submit="createRole" class="flex flex-col gap-4">
            <flux:input wire:model="name" :label="__('Nom du rôle')" required autofocus placeholder="Auditeur" />

            @foreach ($this->permissionGroups() as $group => $permissions)
                <div>
                    <flux:heading size="sm" class="mb-2!">{{ $group }}</flux:heading>
                    <div class="space-y-1">
                        @foreach ($permissions as $permission)
                            <flux:checkbox wire:model="permissions" :value="$permission->value"
                                :label="$this->permissionLabel($permission)" />
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">
                        {{ __('Annuler') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Créer le rôle') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-role" class="md:w-[32rem]">
        <flux:heading size="lg">Modifier le rôle</flux:heading>

        <form wire:submit="saveEditing" class="flex flex-col gap-4">
            <flux:input wire:model="editName" :label="__('Nom du rôle')" required />

            @foreach ($this->permissionGroups() as $group => $permissions)
                <div>
                    <flux:heading size="sm" class="mb-2!">{{ $group }}</flux:heading>
                    <div class="space-y-1">
                        @foreach ($permissions as $permission)
                            <flux:checkbox wire:model="editPermissions" :value="$permission->value"
                                :label="$this->permissionLabel($permission)" />
                        @endforeach
                    </div>
                </div>
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
</div>
