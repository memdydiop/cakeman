<div class="space-y-6">
    <x-page-header
        title="Annonces"
        subtitle="Messages diffusés aux boutiques"
        :breadcrumbs="[['label' => __('Annonces')]]" />

    <flux:card>
        <x-card-header icon="speaker-wave" title="Annonces publiées"
            subtitle="Diffusion centrale vers tous les tenants" :actions="[
                [
                    'label' => __('Nouvelle annonce'),
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'create-announcement\').show()',
                ],
            ]" />

        <x-card-body>
            <flux:table :paginate="$this->announcements">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Titre</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Message</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Date</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->announcements as $a)
                        <flux:table.row :key="$a->id">
                            <flux:table.cell class="font-medium max-w-xs truncate">{{ $a->title }}</flux:table.cell>
                            <flux:table.cell class="max-w-md">
                                <div class="line-clamp-2 text-sm text-zinc-600">{{ $a->body }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap text-sm tabular-nums text-zinc-500">{{ $a->published_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:button variant="subtle" size="xs" icon="trash" :tooltip="__('Supprimer')" wire:click="delete({{ $a->id }})" wire:confirm="Supprimer cette annonce ?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-announcement" >
        <form wire:submit="publish">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvelle annonce</flux:heading>
                    <flux:subheading>Diffusée à toutes les boutiques — notifiée au dashboard</flux:subheading>
                </div>

                <flux:input label="Titre" wire:model="title" placeholder="Ex : Maintenance prévue" />

                <flux:textarea label="Message" wire:model="body" rows="4" placeholder="Détails de l'annonce…" />

                <div class="flex items-center justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-announcement').close()">{{ __('Annuler') }}</flux:button>
                    <flux:button variant="primary" type="submit" icon="paper-airplane">{{ __('Publier') }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
