<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Activer mon compte')"
        :description="__('Choisissez un mot de passe pour rejoindre la console Cakeman.')"
    />

    @php $invitation = $this->invitation; @endphp

    @if ($invitation === null)
        <flux:callout color="red" icon="x-circle">
            <flux:callout.text>{{ __('Ce lien est invalide ou a expiré.') }}</flux:callout.text>
        </flux:callout>
    @elseif ($invitation->accepted_at !== null)
        <flux:callout color="red" icon="x-circle">
            <flux:callout.text>{{ __('Cette invitation a déjà été utilisée.') }}</flux:callout.text>
        </flux:callout>
    @elseif (! $invitation->isValid())
        <flux:callout color="red" icon="x-circle">
            <flux:callout.text>{{ __('Ce lien a expiré.') }}</flux:callout.text>
        </flux:callout>
    @else
        <form wire:submit="accept" class="flex flex-col gap-6">
            @error('token')
                <flux:callout color="red" icon="x-circle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
            <flux:input
                wire:model="password"
                :label="__('Mot de passe')"
                type="password"
                required
                autofocus
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
                {{ __('Activer mon compte') }}
            </flux:button>
        </form>
    @endif
</div>