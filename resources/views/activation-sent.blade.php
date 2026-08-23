<x-layouts::auth.simple :title="__('Vérifiez votre e-mail')">
    <div class="flex flex-col gap-4 text-center">
        <flux:text>
            Un lien d'activation vient de vous être envoyé à l'adresse indiquée.
            Il est valable 48 heures.
        </flux:text>

        <flux:text class="text-sm text-zinc-500">
            Cliquez sur le lien pour choisir votre mot de passe et activer votre boutique.
            Si vous ne recevez rien, pensez à vérifier vos courriers indésirables.
        </flux:text>

        <div class="mt-4">
            <flux:button variant="primary" :href="route('login')" class="w-full">
                {{ __('Aller à la connexion') }}
            </flux:button>
        </div>
    </div>
</x-layouts::auth.simple>
