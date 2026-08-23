<div class="space-y-6">
    <x-page-header title="Plans &amp; add-ons" subtitle="Grille tarifaire lue par la landing et la console" />

    <flux:card class="p-6">
        <x-card-header
            icon="tag"
            title="Plans"
            subtitle="4 paliers par défaut — la landing lit cette grille"
            :actions="[
                [
                    'label' => 'Nouveau plan',
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'edit-plan\').show()',
                    'wire:click' => 'startEditingPlan(null)',
                ],
            ]"
        />

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Plan</flux:table.column>
                    <flux:table.column>Mensuel</flux:table.column>
                    <flux:table.column>Annuel</flux:table.column>
                    <flux:table.column>Limites</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->plans as $plan)
                        <flux:table.row :key="$plan->id">
                            <flux:table.cell class="font-medium">{{ $plan->name }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($plan->monthly_price, 0, ',', ' ') }} FCFA</flux:table.cell>
                            <flux:table.cell>{{ number_format($plan->annual_price, 0, ',', ' ') }} FCFA</flux:table.cell>
                            <flux:table.cell>
                                {{ $plan->limits['users'] ?? '∞' }} utilisateurs ·
                                {{ $plan->limits['orders_per_month'] ?? '∞' }} commandes ·
                                {{ $plan->limits['storage_gb'] ?? '∞' }} Go
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$plan->is_active ? 'green' : 'zinc'">
                                    {{ $plan->is_active ? 'Actif' : 'Inactif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">

                            <flux:dropdown>
                                <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil" title="Modifier" wire:click="startEditingPlan({{ $plan->id }})" />

                                    <flux:menu.item 
                                        :icon="$plan->is_active ? 'eye-slash' : 'eye'" 
                                        :title="$plan->is_active ? 'Désactiver' : 'Activer'"
                                        wire:click="togglePlan({{ $plan->id }})" />    
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
        <x-card-header
            icon="puzzle-piece"
            title="Add-ons"
            subtitle="Options à la carte facturées au prorata"
            :actions="[
                [
                    'label' => 'Nouvel add-on',
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'edit-addon\').show()',
                    'wire:click' => 'startEditingAddon(null)',
                ],
            ]"
        />

        <x-card-body>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Add-on</flux:table.column>
                    <flux:table.column>Prix</flux:table.column>
                    <flux:table.column>Unité</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->addons as $addon)
                        <flux:table.row :key="$addon->id">
                            <flux:table.cell class="font-medium">{{ $addon->name }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($addon->monthly_price, 0, ',', ' ') }} FCFA</flux:table.cell>
                            <flux:table.cell>{{ $addon->unit ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$addon->is_active ? 'green' : 'zinc'">
                                    {{ $addon->is_active ? 'Actif' : 'Inactif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown>
                                    <flux:button variant="subtle" size="xs" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil" title="Modifier" wire:click="startEditingAddon({{ $addon->id }})" />

                                        <flux:menu.item 
                                            :icon="$addon->is_active ? 'eye-slash' : 'eye'" 
                                            :title="$addon->is_active ? 'Désactiver' : 'Activer'"
                                            wire:click="toggleAddon({{ $addon->id }})"
                                            />
                                            
                                    </flux:menu>    
                                </flux:dropdown>  
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="edit-plan" class="md:w-[32rem]">
        <flux:heading size="lg">{{ $this->editingPlanId ? 'Modifier le plan' : 'Nouveau plan' }}</flux:heading>

        <form wire:submit="savePlan" class="flex flex-col gap-4">
            <flux:input wire:model="planName" :label="__('Nom')" required autofocus placeholder="Gold" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="planMonthlyPrice" :label="__('Prix mensuel (FCFA)')" type="number" min="0" required />
                <flux:input wire:model="planAnnualPrice" :label="__('Prix annuel (FCFA)')" type="number" min="0" required />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="planUsersLimit" :label="__('Utilisateurs')" type="number" min="0" />
                <flux:input wire:model="planOrdersLimit" :label="__('Commandes/mois')" type="number" min="0" />
                <flux:input wire:model="planStorageLimit" :label="__('Stockage (Go)')" type="number" min="0" />
            </div>

            <flux:textarea
                wire:model="planFeatures"
                :label="__('Arguments marketing (une ligne = un argument)')"
                rows="6"
                placeholder="3 utilisateurs&#10;60 commandes/mois"
            />

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

    <flux:modal name="edit-addon" class="md:w-[28rem]">
        <flux:heading size="lg">{{ $this->editingAddonId ? 'Modifier l\'add-on' : 'Nouvel add-on' }}</flux:heading>

        <form wire:submit="saveAddon" class="flex flex-col gap-4">
            <flux:input wire:model="addonName" :label="__('Nom')" required autofocus placeholder="Support prioritaire" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addonMonthlyPrice" :label="__('Prix mensuel (FCFA)')" type="number" min="0" required />
                <flux:input wire:model="addonUnit" :label="__('Unité')" placeholder="user, 10gb, 500_msg, month" />
            </div>

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
