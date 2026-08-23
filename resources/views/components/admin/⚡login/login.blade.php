<div class="flex flex-col gap-6">
        <x-auth-header :title="__('Console d\'administration')" :description="__('Espace réservé à l\'équipe Cakeman')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($this->pendingAdminId)
            <form wire:submit="confirmChallenge" class="flex flex-col gap-6">
                <flux:subheading>
                    {{ __('Saisissez le code à 6 chiffres de votre application d\'authentification.') }}
                </flux:subheading>

                <flux:input
                    wire:model="code"
                    :label="__('Code d\'authentification')"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    placeholder="123456"
                />

                <flux:error name="code" />

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Vérifier') }}
                </flux:button>

                <flux:button variant="ghost" type="button" wire:click="$set('pendingAdminId', null)" class="w-full">
                    {{ __('Retour à la connexion') }}
                </flux:button>
            </form>
        @else
            <form wire:submit="authenticate" class="flex flex-col gap-6">
                <flux:input
                    wire:model="email"
                    :label="__('E-mail')"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin@cakeman.test"
                />

                <flux:input
                    wire:model="password"
                    :label="__('Mot de passe')"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                <flux:checkbox wire:model="remember" :label="__('Se souvenir de moi')" />

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Se connecter') }}
                </flux:button>
            </form>
        @endif
    </div>