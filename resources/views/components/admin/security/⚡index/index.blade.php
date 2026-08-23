<div>
    <x-page-header
        title="Sécurité"
        subtitle="Double authentification et mot de passe de votre compte console"
        :breadcrumbs="[['label' => 'Sécurité']]" />

    <flux:card class="p-6 max-w-2xl">
        <x-card-header icon="shield-check" title="Double authentification (TOTP)" subtitle="Protège l'accès à la console (CdC § 12)" />

        <x-card-body>
            @if ($this->confirmationPending)
                <div class="flex flex-col items-center gap-4">
                    <div class="rounded-xl border border-zinc-200 p-4">
                        {!! $this->admin->twoFactorQrCodeSvg() !!}
                    </div>

                    <flux:subheading>
                        Scannez ce QR code avec votre application d'authentification (Google Authenticator, 1Password…)
                    </flux:subheading>

                    <flux:subheading>Codes de récupération (à conserver précieusement) :</flux:subheading>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($this->recoveryCodes() as $code)
                            <div class="rounded-md bg-zinc-100 px-3 py-1.5 font-mono text-xs">{{ $code }}</div>
                        @endforeach
                    </div>

                    <form wire:submit="confirm" class="w-full space-y-3">
                        <flux:input
                            wire:model="code"
                            :label="__('Code de confirmation')"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            required
                            autofocus
                            placeholder="123456"
                        />

                        <flux:error name="code" />

                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Confirmer l\'activation') }}
                        </flux:button>
                    </form>
                </div>
            @elseif ($this->admin->two_factor_secret !== null)
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading>Activée</flux:heading>
                        <flux:subheading>
                            Un code à 6 chiffres est exigé à chaque connexion à la console.
                        </flux:subheading>
                    </div>

                    <flux:button variant="danger" icon="x-circle" wire:click="disable"
                        wire:confirm="Désactiver la double authentification ?">
                        {{ __('Désactiver') }}
                    </flux:button>
                </div>
            @else
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading>Non activée</flux:heading>
                        <flux:subheading>
                            Recommandé : un code à 6 chiffres sera exigé à chaque connexion.
                        </flux:subheading>
                    </div>

                    <flux:button variant="primary" icon="shield-check" wire:click="enable">
                        {{ __('Activer') }}
                    </flux:button>
                </div>
            @endif
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 max-w-2xl mt-4">
        <x-card-header icon="finger-print" title="Passkeys" subtitle="Clés d'accès sans mot de passe (WebAuthn)" />
        <x-card-body>
            @forelse ($this->passkeys as $passkey)
                <div class="flex items-center justify-between gap-4 py-2 border-b border-dashed border-zinc-200 dark:border-zinc-800 last:border-0">
                    <div>
                        <flux:text class="font-medium text-sm">{{ $passkey->name }}</flux:text>
                        <flux:text class="text-xs text-zinc-500">{{ $passkey->created_at->diffForHumans() }} — dernier usage : {{ $passkey->last_used_at?->diffForHumans() ?? 'jamais' }}</flux:text>
                    </div>
                    <flux:button variant="ghost" size="xs" icon="trash" wire:click="deletePasskey({{ $passkey->id }})" wire:confirm="Supprimer cette passkey ?" />
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">{{ __('Aucune passkey enregistrée.') }}</flux:text>
            @endforelse

            <div x-data="{ supported: false, showForm: false, name: '', loading: false, error: null, init() { this.supported = Boolean(window.Passkeys?.isSupported()); window.addEventListener('passkeys:ready', () => this.supported = Boolean(window.Passkeys?.isSupported()), { once: true }); }, async register() { if (!this.name.trim()) return; this.loading = true; this.error = null; try { await window.Passkeys.register({ name: this.name, routes: { options: '{{ route('admin.passkeys.options') }}', submit: '{{ route('admin.passkeys.store') }}' } }); this.name = ''; this.showForm = false; $wire.$refresh(); } catch (e) { if (e.constructor?.name !== 'UserCancelledError') this.error = e.message; } finally { this.loading = false; } } }" class="mt-4">
                <template x-if="!supported"><flux:text>{{ __('Passkeys non supportées par ce navigateur.') }}</flux:text></template>
                <template x-if="supported && !showForm"><div><flux:button variant="primary" icon="plus" x-on:click="showForm = true">{{ __('Ajouter une passkey') }}</flux:button></div></template>
                <template x-if="supported && showForm"><div class="space-y-2 rounded-lg border border-zinc-200 p-4 bg-zinc-50"><flux:input label="Nom de la passkey" x-model="name" placeholder="ex. MacBook Pro" /><p x-show="error" x-text="error" class="text-sm text-red-600"></p><div class="flex gap-2"><flux:button variant="primary" x-on:click="register()" x-bind:disabled="loading || !name.trim()"><span x-show="!loading">{{ __('Enregistrer') }}</span><span x-show="loading">{{ __('Enregistrement…') }}</span></flux:button><flux:button variant="ghost" x-on:click="showForm = false; name=''; error=null">{{ __('Annuler') }}</flux:button></div></div></template>
            </div>
        </x-card-body>
    </flux:card>

    <flux:card class="p-6 max-w-2xl mt-4">
        <x-card-header icon="key" title="Mot de passe" subtitle="Changer votre mot de passe d'accès à la console" />

        <x-card-body>
            <form wire:submit="changePassword" class="space-y-3">
                <flux:input
                    wire:model="currentPassword"
                    :label="__('Mot de passe actuel')"
                    type="password"
                    autocomplete="current-password"
                    required
                    viewable
                />

                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input
                        wire:model="password"
                        :label="__('Nouveau mot de passe')"
                        type="password"
                        autocomplete="new-password"
                        required
                        viewable
                    />

                    <flux:input
                        wire:model="passwordConfirmation"
                        :label="__('Confirmation')"
                        type="password"
                        autocomplete="new-password"
                        required
                        viewable
                    />
                </div>

                <flux:error name="currentPassword" />
                <flux:error name="password" />
                <flux:error name="passwordConfirmation" />

                <flux:subheading>
                    Les autres sessions actives seront déconnectées.
                </flux:subheading>

                <flux:button variant="primary" type="submit" icon="key">
                    {{ __('Modifier le mot de passe') }}
                </flux:button>
            </form>
        </x-card-body>
    </flux:card>
</div>