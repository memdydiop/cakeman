<div class="flex flex-col gap-4">

    <flux:heading size="lg">Nouvelle boutique</flux:heading>
<flux:subheading>Le gérant recevra une invitation par e-mail pour activer la boutique</flux:subheading>

    <form wire:submit="create" class="flex flex-col gap-4">
        <flux:input
            wire:model="name"
            :label="__('Raison sociale')"
            type="text"
            required
            autofocus
            placeholder="Pâtisserie Douceur d'Abidjan"
        />

        <flux:input
            wire:model="subdomain"
            :label="__('Sous-domaine')"
            type="text"
            required
            prefix="https://"
            suffix=".{{ config('tenancy.root_domain') }}"
            placeholder="douceur-abidjan"
        />

        <flux:input
            wire:model="managerName"
            :label="__('Nom du gérant')"
            type="text"
            required
            placeholder="Aya Kouassi"
        />

        <flux:input
            wire:model="managerEmail"
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
                {{ __('Créer la boutique') }}
            </flux:button>
        </div>
    </form>
</div>
