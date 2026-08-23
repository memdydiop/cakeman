<div class="space-y-6">
    <x-page-header
        title="Tickets support"
        subtitle="Helpdesk central — suivi des demandes boutiques"
        :breadcrumbs="[
            ['label' => __('Tickets')],
        ]" />

    <flux:card>
        <x-card-header icon="lifebuoy" title="Tickets"
            subtitle="Filtres et recherche — assignation et clôture" :actions="[
                [
                    'label' => __('Nouveau ticket'),
                    'icon' => 'plus',
                    'x-on:click' => '$flux.modal(\'create-ticket\').show()',
                ],
            ]" />

        <x-card-body>
            <x-table-toolbar search-placeholder="Rechercher (sujet, boutique)…" :per-page="'perPage'">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.status" size="sm" class="w-36">
                        <flux:select.option value="all">Tous statuts</flux:select.option>
                        <flux:select.option value="open">Ouvert</flux:select.option>
                        <flux:select.option value="pending">En cours</flux:select.option>
                        <flux:select.option value="closed">Fermé</flux:select.option>
                    </flux:select>
                    <flux:select wire:model.live="filters.priority" size="sm" class="w-36">
                        <flux:select.option value="all">Toutes priorités</flux:select.option>
                        <flux:select.option value="high">Haute</flux:select.option>
                        <flux:select.option value="medium">Moyenne</flux:select.option>
                        <flux:select.option value="low">Basse</flux:select.option>
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->tickets">
                <flux:table.columns>
                    <flux:table.column>Sujet</flux:table.column>
                    <flux:table.column>Boutique</flux:table.column>
                    <flux:table.column>Priorité</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column>Assigné</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->tickets as $ticket)
                        <flux:table.row :key="$ticket->id">
                            <flux:table.cell class="max-w-xs">
                                <div class="font-medium truncate">{{ $ticket->subject }}</div>
                                <div class="text-xs text-zinc-500 line-clamp-1">{{ $ticket->body }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium">{{ $ticket->tenant->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $ticket->tenant->domains->first()?->domain ?? '' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$this->priorityColor($ticket->priority)">{{ $ticket->priority }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$ticket->status === 'closed' ? 'zinc' : ($ticket->status === 'pending' ? 'amber' : 'green')">{{ $this->statusLabel($ticket->status) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $ticket->assignee?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    @if (!$ticket->assigned_to)
                                        <flux:button variant="subtle" size="xs" icon="user-plus" :tooltip="__('Prendre')" wire:click="assignToMe({{ $ticket->id }})" />
                                    @endif
                                    @if ($ticket->isOpen())
                                        <flux:button variant="subtle" size="xs" icon="chat-bubble-left-right" :tooltip="__('Répondre')" wire:click="openReply({{ $ticket->id }})" />
                                        <flux:button variant="subtle" size="xs" icon="x-circle" :tooltip="__('Fermer')" wire:click="closeTicket({{ $ticket->id }})" wire:confirm="Fermer ce ticket ?" />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-ticket" variant="flyout">
        <form wire:submit="createTicket">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouveau ticket</flux:heading>
                    <flux:subheading>Créer une demande liée à une boutique</flux:subheading>
                </div>

                <flux:select label="Boutique" wire:model="ticketTenantId" placeholder="Choisir une boutique…">
                    @foreach ($this->tenantOptions as $opt)
                        <flux:select.option :value="$opt['id']">{{ $opt['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input label="Sujet" wire:model="ticketSubject" placeholder="Ex: Problème de facturation" />

                <flux:textarea label="Description" wire:model="ticketBody" rows="4" placeholder="Décrivez la demande…" />

                <flux:select label="Priorité" wire:model="ticketPriority">
                    <flux:select.option value="low">Basse</flux:select.option>
                    <flux:select.option value="medium">Moyenne</flux:select.option>
                    <flux:select.option value="high">Haute</flux:select.option>
                </flux:select>

                <div class="flex items-center justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-ticket').close()">{{ __('Annuler') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Créer') }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="reply-ticket" variant="flyout">
        <form wire:submit="addReply">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Répondre</flux:heading>
                    <flux:subheading>Message interne lié au ticket</flux:subheading>
                </div>

                <flux:textarea label="Message" wire:model="replyBody" rows="4" placeholder="Votre réponse…" />

                <div class="flex items-center justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('reply-ticket').close()">{{ __('Annuler') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Envoyer') }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
