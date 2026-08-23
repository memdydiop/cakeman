<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Nouveau mot de passe')"
        :description="__('Choisissez un nouveau mot de passe pour votre compte console.')"
    />

    @if ($this->resetEmail === null)
        <flux:callout color="red" icon="x-circle">
            <flux:callout.text>{{ __('Ce lien est invalide ou a expiré.') }}</flux:callout.text>
        </flux:callout>
    @else
        <form wire:submit="resetPassword" class="flex flex-col gap-6">
            <flux:input
                wire:model="email"
                :label="__('E-mail')"
                type="email"
                required
                autofocus
                autocomplete="email"
            />

            <flux:input
                wire:model="password"
                :label="__('Nouveau mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                wire:model="passwordConfirmation"
                :label="__('Confirmation du mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Enregistrer le nouveau mot de passe') }}
            </flux:button>
        </form>
    @endif
</div>