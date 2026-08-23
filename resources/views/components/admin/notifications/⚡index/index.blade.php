<div class="space-y-6">
    <x-page-header title="Notifications" subtitle="Centre — alertes système et tickets" :breadcrumbs="[['label' => 'Notifications']]" />

    <flux:card>
        <x-card-header icon="bell" title="Notifications" subtitle="Fil d'alertes centrales" :actions="[
            ['label' => 'Tout marquer lu', 'icon' => 'check', 'wire:click' => 'markAllRead'],
        ]" />
        <x-card-body>
            <div class="space-y-3">
                @forelse ($this->notifications as $n)
                    <div class="flex items-start justify-between gap-3 rounded-lg border p-3 {{ $n->read_at ? 'border-zinc-200 bg-white' : 'border-amber-200 bg-amber-50' }}">
                        <div class="min-w-0">
                            <div class="font-medium text-sm">{{ $n->title }}</div>
                            @if ($n->body)
                                <div class="text-sm text-zinc-600">{{ $n->body }}</div>
                            @endif
                            <div class="text-xs text-zinc-500">{{ $n->created_at->diffForHumans() }} · {{ $n->type }}</div>
                        </div>
                        @if (!$n->read_at)
                            <flux:button size="xs" variant="subtle" wire:click="markRead({{ $n->id }})">Lu</flux:button>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">Aucune notification.</flux:text>
                @endforelse
                <div class="mt-4">{{ $this->notifications->links() }}</div>
            </div>
        </x-card-body>
    </flux:card>
</div>
