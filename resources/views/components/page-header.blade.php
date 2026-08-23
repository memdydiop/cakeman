@props([
    'title' => null,
    'subtitle' => null,
    'home' => null,
    'breadcrumbs' => [],
])

@php
    $home = $home ?? [
        'label' => __('Dashboard'),
        'href' => route('admin.dashboard'),
    ];

    $homeAttributes = new \Illuminate\View\ComponentAttributeBag([
        'wire:navigate' => '',
    ]);
@endphp

<div class="my-4 py-2.5 px-4 bg-white rounded-md shadow-(--card-shadow) flex items-center justify-between gap-4">
    <div class="min-w-0">
        @if ($title)
            <flux:heading size="lg" class="mb-0!">{{ $title }}</flux:heading>
        @endif
        @if ($subtitle)
            <flux:subheading class="truncate">{{ $subtitle }}</flux:subheading>
        @endif
    </div>

    <flux:breadcrumbs aria-label="Breadcrumb">
        <flux:breadcrumbs.item
            :href="$home['href']"
            :icon="$home['icon'] ?? 'home'"
            :attributes="$homeAttributes"
        />

        @foreach ($breadcrumbs as $breadcrumb)
            @php
                $crumbAttributes = new \Illuminate\View\ComponentAttributeBag(
                    ! empty($breadcrumb['wire_navigate']) ? ['wire:navigate' => ''] : [],
                );
            @endphp

            <flux:breadcrumbs.item
                :href="$breadcrumb['href'] ?? null"
                :icon="$breadcrumb['icon'] ?? null"
                :attributes="$crumbAttributes"
            >
                {{ $breadcrumb['label'] ?? '' }}
            </flux:breadcrumbs.item>
        @endforeach
    </flux:breadcrumbs>
</div>
