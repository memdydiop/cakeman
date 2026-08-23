<x-layouts::app :title="__('Dashboard')">
    @if (session('impersonated'))
        <div class="mb-4 rounded-lg bg-amber-100 border border-amber-300 p-3 flex items-center justify-between">
            <span class="text-sm font-medium text-amber-800">{{ __('Mode impersonation — vous naviguez en tant que gérant.') }}</span>
            <form method="POST" action="{{ route('tenant.impersonation.leave') }}">@csrf<button type="submit" class="text-sm underline text-amber-800">{{ __('Quitter') }}</button></form>
        </div>
    @endif

    <x-page-header title="Tableau de bord" subtitle="Vue d'ensemble de votre boutique" />

    @php
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                $announcements = tenancy()->central(fn () => \App\Models\Announcement::whereNotNull('published_at')->orderByDesc('published_at')->limit(5)->get());
            } else {
                $announcements = \App\Models\Announcement::whereNotNull('published_at')->orderByDesc('published_at')->limit(5)->get();
            }
        } catch (\Throwable $e) {
            $announcements = collect();
        }
    @endphp
    @if ($announcements->isNotEmpty())
        @php
            try {
                $ackedIds = \Illuminate\Support\Facades\DB::table('announcement_acks')->where('user_id', auth()->id())->pluck('announcement_id')->all();
            } catch (\Throwable $e) {
                $ackedIds = [];
            }
        @endphp
        <div class="mb-4 space-y-2">
            @foreach ($announcements as $announcement)
                @if (! in_array($announcement->id, $ackedIds))
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 flex justify-between gap-4">
                        <div>
                            <div class="font-medium text-blue-900">{{ $announcement->title }}</div>
                            <div class="text-sm text-blue-700 mt-1">{{ $announcement->body }}</div>
                            <div class="text-xs text-blue-500 mt-1">{{ $announcement->published_at->diffForHumans() }}</div>
                        </div>
                        <form method="POST" action="{{ route('announcements.ack', $announcement) }}">@csrf<button type="submit" class="text-xs underline text-blue-600 shrink-0">{{ __('Lu') }}</button></form>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @php
        try {
            $ordersCount = \App\Models\Order::count();
            $revenue = \App\Models\Order::where('status', 'paid')->sum('total');
            $clientsCount = \App\Models\Client::count();
            $lowStock = \App\Models\Ingredient::whereNotNull('low_stock_threshold')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
            $monthlyRevenue = collect(range(5, 0))->map(function (int $offset): int {
                $start = now()->subMonths($offset)->startOfMonth();
                $end = now()->subMonths($offset)->endOfMonth();
                return (int) \App\Models\Order::whereBetween('created_at', [$start, $end])->sum('total');
            })->all();
            $recentOrders = \App\Models\Order::with('client')->latest()->limit(5)->get();
            $topProducts = \App\Models\OrderItem::select('product_id')->selectRaw('SUM(quantity) as total_qty')->groupBy('product_id')->orderByDesc('total_qty')->limit(5)->with('product')->get();
        } catch (\Throwable) {
            $ordersCount = $revenue = $clientsCount = $lowStock = 0;
            $monthlyRevenue = [0,0,0,0,0,0];
            $recentOrders = collect();
            $topProducts = collect();
        }
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-widget icon="banknotes" color="green" title="Chiffre d'affaires" :value="number_format($revenue,0,',',' ').' FCFA'" trend-text="payé" trend-color="text-emerald-600" :sparkline="$monthlyRevenue" />
        <x-widget icon="shopping-cart" color="blue" title="Commandes" :value="$ordersCount" trend-text="total" trend-color="text-blue-600" :href="route('tenant.orders.index')" />
        <x-widget icon="users" color="indigo" title="Clients" :value="$clientsCount" trend-text="actifs" trend-color="text-indigo-600" :href="route('tenant.clients.index')" />
        <x-widget icon="exclamation-triangle" color="red" title="Stock faible" :value="$lowStock" trend-text="ingrédients" :trend-color="$lowStock>0 ? 'text-red-600' : 'text-green-600'" :href="route('tenant.ingredients.index')" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3 mt-4">
        <flux:card class="p-6 lg:col-span-2">
            <x-card-header icon="chart-bar-square" title="Revenu mensuel" subtitle="6 derniers mois" />
            <x-card-body>
                <div class="h-44">
                    <chart:area
                        :series="[['name' => 'Revenu', 'data' => $monthlyRevenue]]"
                        :categories="collect(range(5, 0))->map(fn(int $o) => now()->subMonths($o)->format('M'))->all()"
                        colors='["#059669"]'
                        height="176"
                    />
                </div>
            </x-card-body>
        </flux:card>
        <flux:card class="p-6">
            <x-card-header icon="cube" title="Top produits" subtitle="Quantités vendues" />
            <x-card-body>
                <div class="space-y-3">
                    @forelse ($topProducts as $item)
                        <div class="flex justify-between text-sm">
                            <span class="truncate">{{ $item->product?->name ?? '—' }}</span>
                            <span class="font-semibold tabular-nums">{{ $item->total_qty }}</span>
                        </div>
                    @empty
                        <flux:text class="text-sm text-zinc-500">Aucune vente.</flux:text>
                    @endforelse
                </div>
            </x-card-body>
        </flux:card>
    </div>

    <flux:card class="p-6 mt-4">
        <x-card-header icon="shopping-cart" title="Dernières commandes" :actions="[['label'=>'Voir tout','href'=>route('tenant.orders.index'),'icon'=>'arrow-right']]" />
        <x-card-body>
            <div class="divide-y divide-zinc-200">
                @forelse ($recentOrders as $order)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <div class="font-medium text-sm">#{{ $order->id }} — {{ $order->client?->name ?? 'Sans client' }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->created_at->diffForHumans() }} · {{ $order->status }}</div>
                        </div>
                        <div class="font-semibold tabular-nums">{{ number_format($order->total,0,',',' ') }} FCFA</div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">Aucune commande.</flux:text>
                @endforelse
            </div>
        </x-card-body>
    </flux:card>
</x-layouts::app>
