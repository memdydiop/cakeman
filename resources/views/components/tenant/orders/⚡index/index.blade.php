<div class="space-y-6">
    <x-page-header title="Commandes" subtitle="Ventes — panier et encaissement" :breadcrumbs="[['label' => 'Commandes']]" />

    <flux:card>
        <x-card-header icon="shopping-cart" title="Commandes" :actions="[
            ['label' => 'Nouvelle commande', 'icon' => 'plus', 'x-on:click' => '$flux.modal(\'create-order\').show()'],
        ]" />
        <x-card-body>
            <x-table-toolbar search-placeholder="Client, occasion, notes…" :per-page="null">
                <x-slot name="filters">
                    <flux:select wire:model.live="filters.status" size="sm" class="w-40">
                        <flux:select.option value="all">Tous statuts</flux:select.option>
                        <flux:select.option value="pending">En attente</flux:select.option>
                        <flux:select.option value="confirmed">Confirmée</flux:select.option>
                        <flux:select.option value="in_production">En prod</flux:select.option>
                        <flux:select.option value="ready">Prête</flux:select.option>
                        <flux:select.option value="delivered">Livrée</flux:select.option>
                        <flux:select.option value="cancelled">Annulée</flux:select.option>
                    </flux:select>
                </x-slot>
            </x-table-toolbar>

            <flux:table :paginate="$this->orders">
                <flux:table.columns>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">#</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Client</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Articles</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Échéance</flux:table.column>
                    <flux:table.column align="end" class="text-xs uppercase tracking-wider text-zinc-400">Total</flux:table.column>
                    <flux:table.column class="text-xs uppercase tracking-wider text-zinc-400">Statut</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell class="font-mono text-sm"><a href="{{ route('tenant.orders.show', $order) }}" wire:navigate class="hover:underline">#{{ $order->id }}</a></flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $order->client?->name ?? '—' }}</div>
                                @if ($order->occasion)<div class="text-xs text-zinc-500">{{ $order->occasion }}</div>@endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->items->sum('quantity') }} articles @if ($order->notes)<div class="text-xs text-zinc-500 line-clamp-1">{{ $order->notes }}</div>@endif</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap text-sm">
                                @php $isOverdue = $order->due_at && $order->due_at->isPast() && !in_array($order->status, ['delivered','cancelled','paid']); @endphp
                                <span class="{{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">{{ $order->due_at?->format('d/m/Y H:i') ?? '—' }}</span>
                                @if ($isOverdue)<flux:badge size="sm" color="red">Retard</flux:badge>@endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="font-semibold tabular-nums">{{ number_format($order->total, 0, ',', ' ') }} FCFA</div>
                                @if ($order->deposit>0)<div class="text-xs text-zinc-500">Acompte {{ number_format($order->deposit,0,',',' ') }} · Reste {{ number_format($order->balance,0,',',' ') }}</div>@endif
                            </flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="match($order->status){'paid'=>'green','delivered'=>'green','confirmed'=>'blue','in_production'=>'blue','ready'=>'amber','cancelled'=>'zinc',default=>'amber'}">{{ $order->status }}</flux:badge></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end">
                                    <flux:dropdown align="end">
                                        <flux:button size="xs" variant="subtle" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item :href="route('tenant.orders.show', $order)" icon="eye" wire:navigate>Voir</flux:menu.item>
                                            <flux:menu.item :href="route('tenant.orders.ticket', $order)" icon="printer" target="_blank">Ticket</flux:menu.item>
                                            @if (in_array($order->status, ['pending','confirmed','in_production','ready']))
                                                <flux:menu.item wire:click="advanceStatus({{ $order->id }})" icon="arrow-right">Avancer ({{ $order->status === 'pending' ? 'confirmé' : ($order->status === 'confirmed' ? 'en prod' : ($order->status === 'in_production' ? 'prêt' : 'livré')) }})</flux:menu.item>
                                            @endif
                                            @if ($order->status === 'pending')
                                                <flux:menu.item wire:click="openRemise({{ $order->id }})" icon="receipt-percent">Remise</flux:menu.item>
                                                <flux:menu.item wire:click="markPaid({{ $order->id }})" icon="check">Payer</flux:menu.item>
                                                <flux:menu.item wire:click="cancel({{ $order->id }})" icon="x-mark" variant="danger">Annuler</flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-card-body>
    </flux:card>

    <flux:modal name="create-order" variant="flyout" class="md:w-[640px]">
        <form wire:submit="create">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouvelle commande sur-mesure</flux:heading>
                    <flux:subheading>Panier {{ number_format($this->cartTotal, 0, ',', ' ') }} FCFA — dépôt {{ number_format($deposit ?? 0, 0, ',', ' ') }} — reste {{ number_format(max(0, $this->cartTotal - ($discount ?? 0) - ($deposit ?? 0)), 0, ',', ' ') }} FCFA</flux:subheading>
                </div>

                <div class="space-y-1">
                    <flux:heading size="sm" class="text-zinc-500">Client</flux:heading>
                    <div class="flex gap-2 items-end">
                        <flux:select label="Client" wire:model="client_id" placeholder="Sans client — vente comptoir" class="flex-1">
                            @foreach ($this->clients as $c)
                                <flux:select.option :value="$c->id">{{ $c->name }} @if ($c->phone) — {{ $c->phone }}@endif</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button icon="plus" variant="ghost" x-on:click="$flux.modal('quick-create-client').show()" :tooltip="__('Nouveau client')" class="shrink-0" />
                    </div>
                </div>

                <div class="space-y-3">
                    <flux:heading size="sm" class="text-zinc-500">Échéance & livraison</flux:heading>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:input type="datetime-local" label="Date/heure retrait ou livraison *" wire:model="due_at" />
                        <flux:input label="Occasion" wire:model="occasion" placeholder="Mariage, anniversaire, baptême…" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:select label="Mode" wire:model="delivery_type">
                            <flux:select.option value="retrait">Retrait boutique</flux:select.option>
                            <flux:select.option value="livraison">Livraison</flux:select.option>
                        </flux:select>
                        <flux:input label="Adresse livraison" wire:model="delivery_address" placeholder="Si livraison…" />
                    </div>
                </div>

                <div class="space-y-3">
                    <flux:heading size="sm" class="text-zinc-500">Articles — détails gâteau par ligne</flux:heading>
                    <div class="grid gap-2 sm:grid-cols-[2fr_80px]">
                        <flux:select wire:model="cart_product_id" placeholder="Produit" label="Produit *">
                            @foreach ($this->products as $p)
                                <flux:select.option :value="$p->id">{{ $p->name }} — {{ number_format($p->price,0,',',' ') }} FCFA</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input type="number" label="Qté *" wire:model="cart_quantity" />
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <flux:select wire:model="cart_saveur" placeholder="Saveur" label="Saveur">
                            <flux:select.option value="">—</flux:select.option>
                            <flux:select.option value="vanille">Vanille</flux:select.option>
                            <flux:select.option value="chocolat">Chocolat</flux:select.option>
                            <flux:select.option value="fraise">Fraise</flux:select.option>
                            <flux:select.option value="citron">Citron</flux:select.option>
                            <flux:select.option value="red-velvet">Red Velvet</flux:select.option>
                        </flux:select>
                        <flux:select wire:model="cart_garniture" placeholder="Garniture" label="Garniture">
                            <flux:select.option value="">—</flux:select.option>
                            <flux:select.option value="chantilly">Chantilly</flux:select.option>
                            <flux:select.option value="ganache">Ganache</flux:select.option>
                            <flux:select.option value="creme-beurre">Crème beurre</flux:select.option>
                            <flux:select.option value="mousse">Mousse</flux:select.option>
                        </flux:select>
                        <flux:select wire:model="cart_forme" placeholder="Forme" label="Forme">
                            <flux:select.option value="">—</flux:select.option>
                            <flux:select.option value="ronde">Ronde</flux:select.option>
                            <flux:select.option value="carree">Carrée</flux:select.option>
                            <flux:select.option value="rectangulaire">Rectangulaire</flux:select.option>
                            <flux:select.option value="coeur">Cœur</flux:select.option>
                        </flux:select>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <flux:input label="Taille" wire:model="cart_taille" placeholder="20cm, 8 pouces…" />
                        <flux:input type="number" label="Portions" wire:model="cart_portions" placeholder="8" />
                        <flux:input label="Design / texte" wire:model="cart_design" placeholder="Joyeux Anniv. Papa…" />
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <flux:input label="Couleurs dominantes" wire:model="cart_couleur" placeholder="Rose/blanc, doré…" />
                        <flux:input label="Thématique" wire:model="cart_thematique" placeholder="Licorne, mariage champêtre…" />
                        <flux:input label="Restrictions" wire:model="cart_allergies" placeholder="Sans gluten, arachide…" />
                    </div>
                    <flux:input type="file" label="Image d'inspiration" wire:model="cart_image" accept="image/*" />
                    <div class="space-y-2 rounded border border-dashed p-3">
                        <flux:input type="number" label="Nombre d'étages" wire:model.live="cart_etages" min="1" max="5" />
                        @if ($cart_etages > 1)
                            <div class="space-y-3">
                                @foreach ($cart_etage_details as $idx => $etage)
                                    <div class="rounded border p-3 space-y-2 bg-zinc-50/50">
                                        <flux:heading size="sm" class="text-zinc-500">Étage {{ $idx + 1 }} @if ($etage['faux'] ?? false)<flux:badge size="sm" color="zinc">Faux gâteau</flux:badge>@endif</flux:heading>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <flux:select wire:model="cart_etage_details.{{ $idx }}.saveur" placeholder="Saveur">
                                                <flux:select.option value="">— Saveur</flux:select.option>
                                                <flux:select.option value="vanille">Vanille</flux:select.option>
                                                <flux:select.option value="chocolat">Chocolat</flux:select.option>
                                                <flux:select.option value="fraise">Fraise</flux:select.option>
                                                <flux:select.option value="citron">Citron</flux:select.option>
                                            </flux:select>
                                            <flux:select wire:model="cart_etage_details.{{ $idx }}.garniture" placeholder="Garniture">
                                                <flux:select.option value="">— Garniture</flux:select.option>
                                                <flux:select.option value="chantilly">Chantilly</flux:select.option>
                                                <flux:select.option value="ganache">Ganache</flux:select.option>
                                                <flux:select.option value="creme-beurre">Crème beurre</flux:select.option>
                                            </flux:select>
                                            <flux:select wire:model="cart_etage_details.{{ $idx }}.forme" placeholder="Forme">
                                                <flux:select.option value="">— Forme</flux:select.option>
                                                <flux:select.option value="ronde">Ronde</flux:select.option>
                                                <flux:select.option value="carree">Carrée</flux:select.option>
                                                <flux:select.option value="coeur">Cœur</flux:select.option>
                                            </flux:select>
                                        </div>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <flux:input label="Taille" wire:model="cart_etage_details.{{ $idx }}.taille" placeholder="20cm" />
                                            <flux:input type="number" label="Portions" wire:model="cart_etage_details.{{ $idx }}.portions" />
                                            <flux:checkbox label="Faux gâteau (polystyrène)" wire:model="cart_etage_details.{{ $idx }}.faux" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end">
                        <flux:button type="button" variant="ghost" icon="plus" wire:click="addToCart">Ajouter au panier</flux:button>
                    </div>
                    @if ($cart)
                        <ul class="divide-y divide-zinc-200 rounded border">
                            @foreach ($cart as $i => $line)
                                @php
                                    $p = $this->products->firstWhere('id', $line['product_id']);
                                    $c = $line['customization'] ?? [];
                                    if (is_string($c)) { $c = ['design' => $c]; }
                                @endphp
                                <li class="flex justify-between p-2 text-sm gap-2">
                                    <span class="min-w-0">
                                        <span class="font-medium">{{ $p?->name }} × {{ $line['quantity'] }}</span>
                                        @if ($c)
                                            @php
                                                $display = collect($c)->except('etages')->filter()->map(fn ($v, $k) => $k.': '.$v)->implode(' · ');
                                                $etages = $c['etages'] ?? null;
                                            @endphp
                                            @if ($display)<span class="text-xs text-zinc-500">— {{ $display }}</span>@endif
                                            @if ($etages)<span class="text-xs text-zinc-500">— Étages: {{ collect($etages)->map(fn ($e, $idx) => 'N'.($idx+1).' '.collect($e)->filter()->map(fn ($v,$k)=> $k.':'.$v)->implode('/'))->implode(' | ') }}</span>@endif
                                        @endif
                                        <span class="text-xs text-zinc-500">— {{ number_format(($p?->price ?? 0) * $line['quantity'],0,',',' ') }} FCFA</span>
                                    </span>
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="removeFromCart({{ $i }})" />
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-right text-sm font-semibold">Sous-total {{ number_format($this->cartTotal,0,',',' ') }} FCFA</div>
                    @endif
                </div>

                <div class="space-y-3">
                    <flux:heading size="sm" class="text-zinc-500">Paiement & notes</flux:heading>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <flux:select label="Coupon" wire:model="coupon_id" placeholder="Aucun">
                            @foreach ($this->couponOptions as $coupon)
                                <flux:select.option :value="$coupon->id">{{ $coupon->code }} — {{ $coupon->value }}{{ $coupon->type === 'percent' ? '%' : ' FCFA' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input type="number" label="Remise (FCFA)" wire:model="discount" />
                        <flux:input type="number" label="Acompte (FCFA)" wire:model="deposit" /></div><div class="grid gap-3 sm:grid-cols-2">
                        <flux:select label="Mode paiement" wire:model="payment_method" placeholder="Choisir">
                            <flux:select.option value="especes">Espèces</flux:select.option>
                            <flux:select.option value="wave">Wave</flux:select.option>
                            <flux:select.option value="orange_money">Orange Money</flux:select.option>
                            <flux:select.option value="virement">Virement</flux:select.option>
                        </flux:select>
                    </div>
                    <flux:textarea label="Notes internes / texte à inscrire / allergies" wire:model="notes" rows="2" placeholder="Ex: Joyeux anniversaire Papa, sans arachide, photo modèle vue…" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-order').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer la commande</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="remise-order" variant="flyout">
        <form wire:submit="saveRemise">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remise</flux:heading>
                    <flux:subheading>Montant en FCFA déduit du sous-total</flux:subheading>
                </div>
                <flux:input type="number" label="Remise (FCFA)" wire:model="remiseDiscount" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('remise-order').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Appliquer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="deliver-order" variant="flyout">
        <form wire:submit="confirmDelivery">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Preuve de livraison</flux:heading>
                    <flux:subheading>Photo et signature du client</flux:subheading>
                </div>
                <flux:input type="file" label="Photo livraison" wire:model="delivery_photo" accept="image/*" />
                <div x-data="{ drawing:false, ctx:null, initCanvas(){ this.ctx = $refs.canvas.getContext('2d'); this.ctx.strokeStyle='#18181b'; this.ctx.lineWidth=2; }, start(e){ this.drawing=true; const r=$refs.canvas.getBoundingClientRect(); this.ctx.beginPath(); this.ctx.moveTo((e.touches?e.touches[0].clientX:e.clientX)-r.left,(e.touches?e.touches[0].clientY:e.clientY)-r.top); }, move(e){ if(!this.drawing) return; const r=$refs.canvas.getBoundingClientRect(); this.ctx.lineTo((e.touches?e.touches[0].clientX:e.clientX)-r.left,(e.touches?e.touches[0].clientY:e.clientY)-r.top); this.ctx.stroke(); $wire.set('delivery_signature', $refs.canvas.toDataURL()); }, stop(){ this.drawing=false; }, clear(){ this.ctx.clearRect(0,0,$refs.canvas.width,$refs.canvas.height); $wire.set('delivery_signature',''); } }" x-init="initCanvas()">
                    <flux:label>Signature</flux:label>
                    <canvas x-ref="canvas" width="400" height="150" class="w-full border border-zinc-200 rounded bg-white touch-none"
                        @mousedown="start($event)" @mousemove="move($event)" @mouseup="stop()" @mouseleave="stop()"
                        @touchstart.prevent="start($event)" @touchmove.prevent="move($event)" @touchend="stop()"></canvas>
                    <flux:button size="xs" variant="ghost" x-on:click="clear()" class="mt-1">Effacer</flux:button>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('deliver-order').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Confirmer livraison</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="quick-create-client" variant="flyout">
        <form wire:submit="createQuickClient">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Nouveau client</flux:heading>
                    <flux:subheading>Créé et sélectionné pour la commande</flux:subheading>
                </div>
                <flux:input label="Nom *" wire:model="quick_client_name" />
                <flux:input label="Téléphone" wire:model="quick_client_phone" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('quick-create-client').close()">Annuler</flux:button>
                    <flux:button variant="primary" type="submit">Créer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
