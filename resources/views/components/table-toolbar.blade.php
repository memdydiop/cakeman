@props([
    'search' => 'search',
    'searchPlaceholder' => 'Rechercher…',
    'perPage' => null,
    'filters' => null,
    'actions' => null,
])

<div {{ $attributes->merge(['class' => 'mb-1 pb-3 border-b border-slate-100 flex items-center justify-between gap-4']) }}>
    
    <div class="flex items-center gap-2">
        @if ($perPage)
            <flux:select wire:model.live="{{ $perPage }}" size="sm" class="w-20">
                <flux:select.option :value="10">10</flux:select.option>
                <flux:select.option :value="25">25</flux:select.option>
                <flux:select.option :value="50">50</flux:select.option>
                <flux:select.option :value="100">100</flux:select.option>
            </flux:select>
        @endif

        <flux:input
            size="sm"
            wire:model.live.debounce.300ms="{{ $search }}"
            icon="magnifying-glass"
            :placeholder="$searchPlaceholder"
            class="w-40!"
        />

        @if ($filters)
            {{ $filters }}
        @endif
    </div>

    @if ($actions)
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
