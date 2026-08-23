<div class="space-y-4">

    <x-page-header title="Dashboard" subtitle="Vue d'ensemble de la plateforme" />

    @php
        $total = max(1, $totalTenants);
        $statusShare = fn(\App\Enums\TenantStatus $status): int => (int) round(
            (($tenantsByStatus[$status->value] ?? 0) / $total) * 100,
        );
        $creationsFlat = array_column($creationsByMonth, 'count');
        $feedStyle = fn (string $action): array => $this->activityStyle($action);
        $feedColors = [
            'blue' => 'bg-blue-500/15 text-blue-600',
            'green' => 'bg-green-500/15 text-green-600',
            'amber' => 'bg-amber-500/15 text-amber-600',
            'red' => 'bg-red-500/15 text-red-600',
            'indigo' => 'bg-indigo-500/15 text-indigo-600',
            'zinc' => 'bg-zinc-500/15 text-zinc-600',
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="building-storefront" color="blue" title="Boutiques" :value="$totalTenants" :trend="$tenantTrend"
            trend-label="vs mois dernier" :sparkline="$creationsFlat" :href="route('admin.tenants.index')" />

        <x-widget icon="shield-check" color="green" title="Actives" :value="$tenantsByStatus[\App\Enums\TenantStatus::Active->value] ?? 0" :trend-text="$statusShare(\App\Enums\TenantStatus::Active) . '%'"
            trend-color="text-green-600" trend-label="des boutiques" />

        <x-widget icon="clock" color="amber" title="En essai" :value="$tenantsByStatus[\App\Enums\TenantStatus::Trialing->value] ?? 0" :trend-text="$statusShare(\App\Enums\TenantStatus::Trialing) . '%'"
            trend-color="text-amber-600" trend-label="des boutiques" />

        <x-widget icon="shield-exclamation" color="red" title="Suspendues / annulées" :value="($tenantsByStatus[\App\Enums\TenantStatus::Suspended->value] ?? 0) +
            ($tenantsByStatus[\App\Enums\TenantStatus::Cancelled->value] ?? 0)"
            :trend-text="$statusShare(\App\Enums\TenantStatus::Suspended) +
                $statusShare(\App\Enums\TenantStatus::Cancelled) .
                '%'" trend-color="text-red-600" trend-label="des boutiques" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="banknotes" color="green" title="MRR" :value="number_format($this->mrr, 0, ',', ' ') . ' FCFA'"
            :trend-text="$this->mrr > 0 ? 'abonnements actifs' : 'aucun revenu'"
            trend-color="text-emerald-600" :sparkline="$this->monthlyRevenue" />

        <x-widget icon="queue-list" color="zinc" title="File d'attente" :value="$this->pendingJobs"
            :trend-text="'échecs : ' . $this->failedJobs"
            :trend-color="$this->failedJobs > 0 ? 'text-red-600' : 'text-green-600'"
            :href="route('admin.monitoring.index')" />

        <x-widget icon="arrow-path" color="blue" title="Webhooks — 24 h" :value="$this->webhooksLast24h['processed']"
            :trend-text="'échecs : ' . $this->webhooksLast24h['failed']"
            :trend-color="$this->webhooksLast24h['failed'] > 0 ? 'text-red-600' : 'text-green-600'"
            :href="route('admin.monitoring.index')" />

        <x-widget icon="megaphone" color="indigo" title="Leads" :value="$this->leadStats['total']"
            :trend-text="'convertis : ' . $this->leadStats['converted']"
            trend-color="text-indigo-600" :sparkline="$this->leadsByMonth" :href="route('admin.leads.index')" />
    </div>

    @php
        $maxCreations = max(1, max(array_column($creationsByMonth, 'count')));
        $statusSegments = [
            [\App\Enums\TenantStatus::Active, 'bg-green-500', '#22c55e'],
            [\App\Enums\TenantStatus::Pending, 'bg-blue-500', '#3b82f6'],
            [\App\Enums\TenantStatus::Trialing, 'bg-amber-500', '#f59e0b'],
            [\App\Enums\TenantStatus::Suspended, 'bg-red-500', '#ef4444'],
            [\App\Enums\TenantStatus::Cancelled, 'bg-zinc-400', '#a1a1aa'],
        ];
        $cumulated = 0;
    @endphp

    <div class="grid gap-4 lg:grid-cols-4">
        <flux:card class="p-6 lg:col-span-3">
            <x-card-header icon="chart-bar-square" title="Croissance des boutiques"
                subtitle="Créations sur les 12 derniers mois" />

            <x-card-body class="">
                <div class="h-44">
                    <chart:area
                        :series="[['name' => 'Boutiques', 'data' => array_column($creationsByMonth, 'count')]]"
                        :categories="array_column($creationsByMonth, 'label')"
                        colors='["#3b82f6"]'
                        height="176"
                    />
                </div>

                <div class="mt-3 flex items-center justify-between border-t border-dashed border-zinc-200 pt-3">
                    <flux:text class="text-xs text-zinc-500">Pic : {{ $maxCreations }} boutiques ({{ $creationsByMonth[array_search($maxCreations, array_column($creationsByMonth, 'count'))]['label'] }})</flux:text>
                    <flux:text class="text-xs font-semibold text-zinc-500">{{ $tenantTrend >= 0 ? '+' : '' }}{{ $tenantTrend }}% vs mois dernier</flux:text>
                </div>
            </x-card-body>
        </flux:card>

        <flux:card class="p-6">
            <x-card-header icon="chart-pie" title="Répartition par statut" subtitle="Ensemble des boutiques" />

            @php
                $donutSegments = [];
                foreach ($statusSegments as [$status, , $color]) {
                    $share = $statusShare($status);
                    if ($share === 0) {
                        continue;
                    }
                    $donutSegments[] = ['color' => $color, 'share' => $share];
                }
                if ($donutSegments === []) {
                    $donutSegments = [['color' => '#a1a1aa', 'share' => 100]];
                }
                $radius = 56;
                $circ = 2 * pi() * $radius;
            @endphp

            <x-card-body class="">
                <div class="flex justify-center">
                    <div class="relative size-40">
                        <svg viewBox="0 0 128 128" class="size-40 -rotate-90" role="img" aria-label="Répartition par statut">
                            @php $offset = 0; @endphp
                            @foreach ($donutSegments as $seg)
                                @php
                                    $len = $circ * ($seg['share'] / 100);
                                    $gap = $circ - $len;
                                @endphp
                                <circle cx="64" cy="64" r="{{ $radius }}" fill="none"
                                    stroke="{{ $seg['color'] }}" stroke-width="20"
                                    stroke-dasharray="{{ $len }} {{ $gap }}"
                                    stroke-dashoffset="{{ -$offset }}"
                                    class="transition-all duration-700" />
                                @php $offset += $len; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-5 flex flex-col items-center justify-center rounded-full bg-white">
                            <flux:heading size="xl">{{ $totalTenants }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500">boutiques</flux:text>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    @foreach ($statusSegments as [$status, $dot])
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-full {{ $dot }}"></span>
                                <flux:text class="text-sm text-zinc-600 ">
                                    {{ $this->statusLabel($status) }}</flux:text>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:text class="text-sm font-semibold tabular-nums">
                                    {{ $tenantsByStatus[$status->value] ?? 0 }}</flux:text>
                                <flux:text class="w-10 text-right text-xs tabular-nums text-zinc-500">
                                    {{ $statusShare($status) }}%</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card-body>
        </flux:card>
        <flux:card class="p-6 lg:col-span-4">
            <x-card-header
                icon="building-storefront"
                title="Dernières boutiques"
                subtitle="Inscriptions récentes sur la plateforme"
                :actions="[
                    [
                        'label' => 'Tout voir',
                        'href' => route('admin.tenants.index'),
                        'icon' => 'arrow-right-start-on-rectangle',
                    ],
                ]" />

            <x-card-body class="">
                <div class="divide-y divide-zinc-200 ">
                    @forelse ($this->recentTenants as $index => $tenant)
                        <div class="flex items-center gap-4 py-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/15 font-semibold text-blue-600 ">
                                {{ $tenant->initials() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <flux:text class="block truncate font-medium">{{ $tenant->name }}</flux:text>
                                <flux:text class="block truncate text-xs text-zinc-500">
                                    {{ $tenant->domains->first()?->domain }}</flux:text>
                            </div>
                            <flux:text class="hidden text-xs text-zinc-500 sm:block">
                                {{ $tenant->created_at->diffForHumans() }}</flux:text>
                        <flux:badge
                            :color="match ($tenant->status) {
                                                                                    \App\Enums\TenantStatus::Active => 'green',
                                                                                    \App\Enums\TenantStatus::Pending => 'blue',
                                                                                    \App\Enums\TenantStatus::Suspended => 'red',
                                                                                    \App\Enums\TenantStatus::Trialing => 'amber',
                                                                                    \App\Enums\TenantStatus::Cancelled => 'zinc',
                                                                                }"
                                size="sm">{{ $this->statusLabel($tenant->status) }}</flux:badge>
                        </div>
                    @empty
                        <flux:text class="py-6 text-sm text-zinc-500">Aucune boutique pour le moment.</flux:text>
                    @endforelse
                </div>
            </x-card-body>
        </flux:card>
            <flux:card class="p-6 lg:col-span-2">
                <x-card-header icon="bolt" title="Activité récente" subtitle="Derniers événements de la console"
                    :actions="[
                        [
                            'label' => 'Journal complet',
                            'href' => route('admin.audit.index'),
                            'icon' => 'arrow-right-start-on-rectangle',
                        ],
                    ]" />

                <x-card-body class="">
                    <ol class="relative space-y-4 before:absolute before:inset-y-1 before:left-[17px] before:w-px before:bg-zinc-200">
                        @forelse ($this->recentActivity as $log)
                            @php
                                $style = $feedStyle($log->action);
                            @endphp
                            <li class="relative flex items-start gap-3">
                                <div class="z-10 flex size-9 shrink-0 items-center justify-center rounded-full {{ $feedColors[$style['color']] ?? $feedColors['zinc'] }}">
                                    <flux:icon :name="$style['icon']" variant="solid" class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <flux:text class="block truncate text-sm font-medium">
                                        {{ $this->activityLabel($log->action) }}</flux:text>
                                    <flux:text class="block truncate text-xs text-zinc-500">
                                        @if ($log->tenant)
                                            {{ $log->tenant->name }} ·
                                        @endif
                                        {{ $log->adminUser?->name ?? 'Système' }}
                                    </flux:text>
                                </div>
                                <flux:text class="shrink-0 pt-0.5 text-xs text-zinc-400">
                                    {{ $log->created_at->diffForHumans() }}
                                </flux:text>
                            </li>
                        @empty
                            <flux:text class="py-4 text-sm text-zinc-500">Aucun événement récent.</flux:text>
                        @endforelse
                    </ol>
                </x-card-body>
            </flux:card>

            <flux:card class="p-6 lg:col-span-2">
                <x-card-header icon="trophy" title="Top boutiques — MRR" subtitle="5 plus gros revenus mensuels" :actions="[
                    [
                        'label' => 'Tout voir',
                        'href' => route('admin.subscriptions.index'),
                        'icon' => 'arrow-right-start-on-rectangle',
                    ],
                ]" />

                <x-card-body class="">
                    <div class="divide-y divide-zinc-200">
                        @forelse ($this->topTenantsByMrr as $i => $row)
                            <div class="flex items-center gap-3 py-2.5">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-500/15 text-xs font-bold text-amber-700">
                                    #{{ $i + 1 }}
                                </div>
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/15 font-semibold text-blue-600">
                                    {{ $row['initials'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <flux:text class="block truncate text-sm font-medium">{{ $row['tenant_name'] }}</flux:text>
                                    <flux:text class="block truncate text-xs text-zinc-500">{{ $row['domain'] }}</flux:text>
                                </div>
                                <flux:text class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900">
                                    {{ number_format($row['mrr'], 0, ',', ' ') }} FCFA</flux:text>
                            </div>
                        @empty
                            <flux:text class="py-6 text-sm text-zinc-500">Aucun revenu pour le moment.</flux:text>
                        @endforelse
                    </div>
                </x-card-body>
            </flux:card>

            <flux:card class="p-6 lg:col-span-4">
                <x-card-header icon="users" title="Équipe console" subtitle="Administrateurs récents" :actions="[
                    [
                        'label' => 'Tout voir',
                        'href' => route('admin.users.index'),
                        'icon' => 'arrow-right-start-on-rectangle',
                    ],
                ]" />

                <x-card-body class="">
                    <div class="divide-y divide-zinc-200 ">
                        @forelse ($this->recentAdminUsers as $admin)
                            <div class="flex items-center gap-3 py-2.5">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/15 font-semibold text-blue-600 ">
                                    {{ $admin->initials() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <flux:text class="block truncate text-sm font-medium">{{ $admin->name }}</flux:text>
                                    <flux:text class="block truncate text-xs text-zinc-500">{{ $admin->email }}
                                    </flux:text>
                                </div>
                                @if ($role = $admin->roles->first())
                                    <flux:badge size="sm" color="indigo">{{ $role->name }}</flux:badge>
                                @endif
                            </div>
                        @empty
                            <flux:text class="py-6 text-sm text-zinc-500">Aucun administrateur.</flux:text>
                        @endforelse
                    </div>
                </x-card-body>
            </flux:card>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">

        <div class="space-y-4">
        </div>
    </div>
</div>