<div>
    <x-page-header
        title="Configuration"
        subtitle="Réglages de la plateforme — pilotés sans déploiement"
        :breadcrumbs="[['label' => 'Configuration']]" />

    <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-4">
        @foreach ([
            'general' => ['Général', 'cog-6-tooth'],
            'billing' => ['Facturation', 'credit-card'],
            'acquisition' => ['Acquisition', 'megaphone'],
            'security' => ['Sécurité', 'shield-check'],
        ] as $tab => [$label, $icon])
            <button type="button" wire:click="selectTab('{{ $tab }}')"
                class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition
                    {{ $activeTab === $tab ? 'bg-blue-500/10 text-blue-600' : 'text-zinc-600 hover:bg-zinc-100' }}">
                <flux:icon :name="$icon" variant="micro" class="size-4" />
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($activeTab === 'general')
        <flux:card class="p-6 mt-4">
            <x-card-header icon="cog-6-tooth" title="Paramètres généraux" subtitle="Plateforme et cycle de vie des boutiques" />

            <x-card-body class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="general.name" label="Nom de la plateforme" required />
                    <flux:input wire:model="general.supportEmail" label="E-mail support" type="email" placeholder="support@exemple.ci" />

                    <flux:input wire:model="general.trialDays" label="Durée d'essai (jours)" type="number" min="1" max="90" />
                    <flux:input wire:model="general.archiveDays" label="Délai avant archivage (jours)" type="number" min="1" max="180" />
                    <flux:input wire:model="general.maintenanceHours" label="Durée maintenance boutique (heures)" type="number" min="1" max="24" />
                </div>

                <flux:textarea wire:model="general.maintenanceMessage" label="Message de maintenance (landing)" rows="3" />

                <flux:switch wire:model="general.maintenanceEnabled" label="Maintenance de la landing publique" />

                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="saveGeneral">
                        {{ __('Enregistrer') }}
                    </flux:button>
                </div>
            </x-card-body>
        </flux:card>
    @endif

    @if ($activeTab === 'billing')
        <flux:card class="p-6 mt-4">
            <x-card-header icon="credit-card" title="Facturation Mobile Money" subtitle="Cycle d'encaissement et agrégateur" />

            <x-card-body class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="billing.graceDays" label="Jours de grâce avant suspension" type="number" min="1" max="30" />
                </div>

                <flux:switch wire:model="billing.sandboxMode" label="Mode sandbox Mobile Money" />
                <flux:input wire:model="billing.webhookSecret" label="Secret webhook Mobile Money" type="password" autocomplete="off" />

                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="saveBilling">
                        {{ __('Enregistrer') }}
                    </flux:button>
                </div>
            </x-card-body>
        </flux:card>
    @endif

    @if ($activeTab === 'acquisition')
        <flux:card class="p-6 mt-4">
            <x-card-header icon="megaphone" title="Acquisition" subtitle="Notification des prospects de la landing" />

            <x-card-body class="space-y-4">
                <flux:input wire:model="acquisition.leadNotificationEmail" label="E-mail de notification des nouveaux leads"
                    type="email" placeholder="ventes@exemple.ci" hint="Reçoit un e-mail à chaque inscription au formulaire de démo." />

                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="saveAcquisition">
                        {{ __('Enregistrer') }}
                    </flux:button>
                </div>
            </x-card-body>
        </flux:card>
    @endif

    @if ($activeTab === 'security')
        <flux:card class="p-6 mt-4">
            <x-card-header icon="shield-check" title="Sécurité" subtitle="Invitations et double authentification" />

            <x-card-body class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="security.invitationExpiresHours" label="Validité des invitations (heures)" type="number" min="1" max="168" />
                </div>

                <flux:switch wire:model="security.twoFactorRequired" label="2FA obligatoire pour la console" />

                <div class="flex justify-end">
                    <flux:button variant="primary" icon="check" wire:click="saveSecurity">
                        {{ __('Enregistrer') }}
                    </flux:button>
                </div>
            </x-card-body>
        </flux:card>
    @endif
</div>