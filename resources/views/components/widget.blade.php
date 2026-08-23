@props([
    'icon' => null,
    'color' => 'blue',
    'title' => null,
    'value' => null,
    'trend' => null,
    'trendIcon' => null,
    'trendColor' => null,
    'trendText' => null,
    'trendLabel' => null,
    'trendLabelColor' => null,
    'href' => null,
    'sparkline' => null,
    'progress' => null,
])

@php
    // YNEX palette — fond 10 % comme la démo CRM (Avatar bg-soft)
    $iconClasses = [
        'blue' => 'bg-blue-500/10 text-blue-600',
        'green' => 'bg-emerald-500/10 text-emerald-600',
        'amber' => 'bg-amber-500/10 text-amber-600',
        'red' => 'bg-red-500/10 text-red-600',
        'indigo' => 'bg-indigo-500/10 text-indigo-600',
        'zinc' => 'bg-zinc-500/10 text-zinc-600',
        'primary' => 'bg-primary/10 text-primary',
    ];

    $isPositive = ($trend ?? 0) >= 0;
    $trendIcon = $trendIcon ?? ($isPositive ? 'arrow-trending-up' : 'arrow-trending-down');
    $trendColor = $trendColor ?? ($isPositive ? 'text-emerald-600' : 'text-red-600');
    $trendText = $trendText ?? abs((int) $trend) . '%';

    $sparkline = collect($sparkline)->map(fn($value) => (float) $value);
    $progress = $progress ?? ($trend !== null ? min(90, max(12, abs((int) $trend) + 32)) : null);

    $sparklineColors = [
        'blue' => 'text-blue-600',
        'green' => 'text-emerald-600',
        'amber' => 'text-amber-600',
        'red' => 'text-red-600',
        'indigo' => 'text-indigo-600',
        'zinc' => 'text-zinc-500',
        'primary' => 'text-primary',
    ];
    $sparklineColor = $sparklineColors[$color] ?? $sparklineColors['zinc'];

    $iconWrap = $iconClasses[$color] ?? $iconClasses['zinc'];
@endphp

<flux:card class="p-0! overflow-hidden border border-[rgb(var(--default-border))]! shadow-none!">

    <div class="p-4 pb-3 flex flex-1 items-start gap-3">

        <div class="flex size-11 shrink-0 items-center justify-center rounded-md {{ $iconWrap }}">
            <flux:icon :name="$icon" variant="solid" class="size-5" />
        </div>

        <div class="flex flex-col flex-1 h-fit">

            <div>
                <div class="text-sm leading-none font-medium text-[rgb(var(--text-muted))] truncate">
                    {{ $title }}
                </div>
                <flux:heading
                    class="mt-1 text-[1.5rem]! leading-none! font-semibold! text-[rgb(var(--default-text-color))]! truncate">
                    {{ $value }}
                </flux:heading>
            </div>
            
            @if ($sparkline->isNotEmpty())
                @php
                    $sparkPoints = $sparkline
                        ->map(
                            fn($value, $i) => ($i / max(1, $sparkline->count() - 1)) * 100 .
                                ',' .
                                (26 - ($value / max(1, $sparkline->max())) * 20),
                        )
                        ->implode(' ');
                    $sparkFillId = 'spark-' . md5($color . $sparkPoints);
                    $sparkArea = $sparkPoints . ' 100,28 0,28';
                    $sparkStrokeMap = [
                        'text-blue-600' => '#2563eb',
                        'text-emerald-600' => '#059669',
                        'text-amber-600' => '#d97706',
                        'text-red-600' => '#dc2626',
                        'text-indigo-600' => '#4f46e5',
                        'text-zinc-500' => '#71717a',
                        'text-primary' => 'rgb(var(--primary))',
                    ];
                    $sparkHex = $sparkStrokeMap[$sparklineColor] ?? '#71717a';
                @endphp
                <svg viewBox="0 0 100 28" preserveAspectRatio="none" class=" w-full h-7" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $sparkFillId }}" x1="0" y1="0" x2="0"
                            y2="1">
                            <stop offset="0%" stop-color="{{ $sparkHex }}" stop-opacity="0.18" />
                            <stop offset="100%" stop-color="{{ $sparkHex }}" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <polygon points="{{ $sparkArea }}" fill="url(#{{ $sparkFillId }})" />
                    <polyline fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                        stroke-linejoin="round" points="{{ $sparkPoints }}" class="{{ $sparklineColor }}" />
                </svg>
            @endif

            <flux:spacer />

            @if ($trend !== null || $trendText !== null)
                <div class="mt-2.5 flex items-center gap-1.5">
                    @if ($trend !== null)
                        <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $trendColor }}">
                            <flux:icon :name="$trendIcon" class="size-3.5" />
                            {{ $trendText }}
                        </span>
                    @else
                        <span class="text-xs font-semibold {{ $trendColor }}">{{ $trendText }}</span>
                    @endif
                    @if ($trendLabel)
                        <span class="text-xs text-[rgb(var(--text-muted))] truncate">{{ $trendLabel }}</span>
                    @endif
                </div>
            @endif

            @if ($progress !== null)
                <div class="h-1 w-full bg-zinc-100">
                    <div class="h-1 {{ str_replace('text-', 'bg-', $sparklineColor) }} transition-all duration-700"
                        style="width: {{ $progress }}%"></div>
                </div>
            @endif
        </div>

    </div>
</flux:card>
