@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'color' => 'blue',
    'actions' => [],
    'href' => null,
])

@php
    $badgeClasses = [
        'blue' => 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
        'green' => 'bg-green-500/15 text-green-600 dark:text-green-400',
        'amber' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
        'red' => 'bg-red-500/15 text-red-600 dark:text-red-400',
        'indigo' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400',
        'zinc' => 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-400',
    ];

    $colorClass = $badgeClasses[$color] ?? $badgeClasses['blue'];
@endphp

<div
    {{ $attributes->merge(['class' => 'px-4 py-2 border-b border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-4']) }}>
    <div class="flex min-w-0 items-center gap-3">
        @if ($icon)
            <div class="{{ $colorClass }} flex size-8 shrink-0 items-center justify-center rounded">
                <flux:icon :name="$icon" variant="solid" class="size-5" />
            </div>
        @endif

        <div class="min-w-0">
            @if ($title)
                <flux:heading class="truncate mb-0!">{{ $title }}</flux:heading>
            @endif
            @if ($subtitle)
                <flux:subheading class="truncate">{{ $subtitle }}</flux:subheading>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-1">
        @if (!empty($actions))
            @foreach ($actions as $action)
                @php
                    $isObject = is_object($action);
                    $actionArray = (array) $action;

                    if (($actionArray['show'] ?? true) === false) {
                        continue;
                    }

                    $label = $isObject ? $action->label ?? '' : $action['label'] ?? '';
                    $actionHref = $isObject ? $action->href ?? $href : $action['href'] ?? $href;
                    $actionIcon = $isObject ? $action->icon ?? null : $action['icon'] ?? null;
                    $actionSize = $isObject ? $action->size ?? 'xs' : $action['size'] ?? 'xs';
                    $actionAttributes = new \Illuminate\View\ComponentAttributeBag(
                        \Illuminate\Support\Arr::except($actionArray, ['label', 'href', 'icon', 'variant', 'size', 'show']),
                    );
                @endphp
                <flux:button class="border"
                    :tooltip="$label"
                    :variant="'filled'"
                    :size="$actionSize"
                    :icon="$actionIcon"
                    :href="$actionHref"
                    :attributes="$actionAttributes"
                />
            @endforeach
        @endif
    </div>
</div>
