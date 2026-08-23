@props([
    'points' => [],
    'height' => 176,
    'color' => '#3b82f6',
    'labelColor' => null,
    'valuePrefix' => '',
    'valueSuffix' => '',
])

@php
    $points = collect($points)->map(fn ($point) => is_array($point)
        ? ['label' => $point['label'] ?? '', 'value' => (float) ($point['value'] ?? 0)]
        : ['label' => (string) $point, 'value' => (float) $point])
        ->values();

    $maxValue = max(1, $points->max('value'));
    $count = max(1, $points->count());

    $plotWidth = 100;
    $plotHeight = 100;
    $padTop = 8;
    $padBottom = 4;

    $step = $count > 1 ? ($plotWidth - 6) / ($count - 1) : 0;
    $inner = $plotHeight - $padTop - $padBottom;

    $coords = $points->map(fn ($point, $i) => [
        'x' => $count > 1 ? 3 + $i * $step : 50,
        'y' => $padTop + $inner - ($point['value'] / $maxValue) * $inner,
    ])->values();

    $path = $coords->map(fn ($c, $i) => ($i === 0 ? 'M' : 'L').$c['x'].','.$c['y'])->implode(' ');

    $area = $path.' L'.($coords->last()['x'] ?? $plotWidth).','.$plotHeight.' L'.($coords->first()['x'] ?? 0).','.$plotHeight.' Z';

    $gridLines = collect(range(0, 3))->map(fn ($i) => $padTop + ($inner / 3) * $i)->all();

    $fillId = 'trend-fill-'.md5($color.$points->implode('value'));
@endphp

<div>
    <svg viewBox="0 0 {{ $plotWidth }} {{ $plotHeight }}" preserveAspectRatio="none"
        class="w-full" style="height: {{ $height }}px" role="img" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $fillId }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.25" />
                <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
            </linearGradient>
        </defs>

        @foreach ($gridLines as $y)
            <line x1="0" x2="{{ $plotWidth }}" y1="{{ $y }}" y2="{{ $y }}"
                class="stroke-zinc-200/70 dark:stroke-zinc-800" stroke-width="0.5" />
        @endforeach

        <path d="{{ $area }}" fill="url(#{{ $fillId }})" />
        <path d="{{ $path }}" fill="none" stroke="{{ $color }}" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" />

        @foreach ($coords as $i => $coord)
            <circle cx="{{ $coord['x'] }}" cy="{{ $coord['y'] }}" r="1.6" fill="{{ $color }}" />
        @endforeach
    </svg>

    <div class="mt-2 flex justify-between gap-1">
        @foreach ($points as $point)
            <span class="flex-1 text-center text-[11px] text-zinc-500">{{ $point['label'] }}</span>
        @endforeach
    </div>
</div>