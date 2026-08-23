    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Créer votre boutique Cakeman')"
            :description="__('Votre espace de vente est prêt en quelques minutes.')"
        />

        <form wire:submit="register" class="flex flex-col gap-6">
            <flux:input
                wire:model="businessName"
                :label="__('Raison sociale')"
                type="text"
                required
                autofocus
                autocomplete="organization"
                placeholder="Pâtisserie Douceur d'Abidjan"
            />

            <flux:input
                wire:model="subdomain"
                :label="__('Sous-domaine de votre boutique')"
                type="text"
                required
                prefix="https://"
                suffix=".{{ config('tenancy.root_domain') }}"
                autocomplete="off"
                placeholder="douceur-abidjan"
                inputmode="text"
            />

            <flux:separator text="Compte du Gérant" />

            @if ($this->selectedPlanName() !== null)
                <flux:callout color="emerald" icon="check-circle" class="!text-left">
                    <flux:callout.heading>Plan {{ $this->selectedPlanName() }} sélectionné</flux:callout.heading>
                    <flux:callout.text>
                        Essai gratuit de 14 jours, sans carte bancaire.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:input
                wire:model="managerName"
                :label="__('Nom complet')"
                type="text"
                required
                autocomplete="name"
                placeholder="Aya Kouassi"
            />

            <flux:input
                wire:model="email"
                :label="__('E-mail')"
                type="email"
                required
                autocomplete="email"
                placeholder="aya@exemple.ci"
                description="Un lien d'activation valable 48 heures vous sera envoyé."
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Créer ma boutique') }}
            </flux:button>
        </form>
    </div>
