<div class="space-y-6">
    <x-page-header :title="'Commande #'.$order->id" :subtitle="$order->client?->name ?? 'Sans client'" :breadcrumbs="[['label' => 'Commandes', 'href' => route('tenant.orders.index')], ['label' => '#'.$order->id]]" />

    <div class="flex flex-wrap gap-2">
        <flux:badge :color="match($order->status){'delivered'=>'green','cancelled'=>'zinc',default=>'amber'}" size="lg">{{ $order->status }}</flux:badge>
        <flux:text class="text-sm text-zinc-500">Échéance {{ $order->due_at?->format('d/m/Y H:i') ?? '—' }} · {{ $order->occasion ?? '' }}</flux:text>
    </div>

    <div class="flex gap-2">
        @if (in_array($order->status, ['pending','confirmed','in_production','ready']))
            <flux:button variant="primary" icon="arrow-right" wire:click="advance">Avancer : {{ ['pending'=>'Confirmer','confirmed'=>'En prod','in_production'=>'Prête','ready'=>'Livrer'][$order->status] ?? 'Suivant' }}</flux:button>
        @endif
        <flux:button variant="ghost" icon="document-duplicate" wire:click="duplicate">Dupliquer</flux:button>
        <a href="{{ route('tenant.orders.ticket', $order) }}" target="_blank"><flux:button variant="ghost" icon="printer">Ticket</flux:button></a>
        @if ($order->tenantInvoice)
            <flux:badge color="green">Facture {{ $order->tenantInvoice->number }}</flux:badge>
        @else
            <flux:text class="text-xs text-zinc-500">Pas de facture</flux:text>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="p-6 lg:col-span-2">
            <x-card-header icon="shopping-cart" title="Articles" />
            <x-card-body>
                <div class="divide-y divide-zinc-200">
                    @foreach ($order->items as $item)
                        @php $c = $item->customization ?? []; @endphp
                        <div class="flex justify-between py-3">
                            <div>
                                <div class="font-medium">{{ $item->product->name }} × {{ $item->quantity }}</div>
                                @if ($c)
                                    <div class="text-xs text-zinc-500">{{ collect($c)->except('etages')->filter()->map(fn($v,$k)=> $k.':'.$v)->implode(' · ') }}</div>
                                    @if (!empty($c['etages']))
                                        <div class="text-xs text-zinc-500">Étages: {{ collect($c['etages'])->map(fn($e,$i)=> 'N'.($i+1).' '.collect($e)->filter()->map(fn($v,$k)=> $k.':'.$v)->implode('/'))->implode(' | ') }}</div>
                                    @endif
                                @endif
                            </div>
                            <div class="font-semibold tabular-nums">{{ number_format($item->line_total,0,',',' ') }} FCFA</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between"><span>Sous-total</span><span class="font-semibold">{{ number_format($order->subtotal,0,',',' ') }} FCFA</span></div>
                    @if ($order->discount>0)<div class="flex justify-between text-emerald-600"><span>Remise</span><span>-{{ number_format($order->discount,0,',',' ') }}</span></div>@endif
                    <div class="flex justify-between font-bold"><span>Total</span><span>{{ number_format($order->total,0,',',' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>Acompte</span><span>{{ number_format($order->deposit,0,',',' ') }}</span></div>
                    <div class="flex justify-between font-bold"><span>Reste</span><span>{{ number_format($order->balance,0,',',' ') }} FCFA</span></div>
                </div>
            </x-card-body>
        </flux:card>

        <flux:card class="p-6">
            <x-card-header icon="truck" title="Livraison & paiement" />
            <x-card-body>
                <div class="space-y-2 text-sm">
                    <div><span class="text-zinc-500">Mode:</span> {{ $order->delivery_type ?? '—' }}</div>
                    <div><span class="text-zinc-500">Adresse:</span> {{ $order->delivery_address ?? '—' }}</div>
                    <div><span class="text-zinc-500">Paiement:</span> {{ $order->payment_method ?? '—' }}</div>
                    <div><span class="text-zinc-500">Notes:</span> {{ $order->notes ?? '—' }}</div>
                    @if ($order->delivery_photo_path)<div><img src="{{ Storage::disk('public')->url($order->delivery_photo_path) }}" class="mt-2 max-w-full rounded border"></div>@endif
                    @if ($order->delivery_signature)<div class="mt-2"><img src="{{ $order->delivery_signature }}" class="max-w-full border rounded bg-white"></div>@endif
                </div>
            </x-card-body>
        </flux:card>
    </div>

    @php $flow = ['pending','confirmed','in_production','ready','delivered']; $idx = array_search($order->status, $flow, true); @endphp
    <flux:card class="p-6">
        <x-card-header icon="clock" title="Suivi" />
        <x-card-body>
            <ol class="flex gap-2">
                @foreach ($flow as $i => $step)
                    <li class="flex-1 text-center">
                        <div class="mx-auto size-8 rounded-full flex items-center justify-center text-xs font-bold {{ $i <= $idx ? 'bg-primary text-white' : 'bg-zinc-200 text-zinc-500' }}">{{ $i+1 }}</div>
                        <div class="text-xs mt-1 {{ $i <= $idx ? 'font-semibold' : 'text-zinc-500' }}">{{ $step }}</div>
                    </li>
                    @if ($i < count($flow)-1)<div class="flex-1 h-0.5 self-center {{ $i < $idx ? 'bg-primary' : 'bg-zinc-200' }}"></div>@endif
                @endforeach
            </ol>
        </x-card-body>
    </flux:card>
</div>
