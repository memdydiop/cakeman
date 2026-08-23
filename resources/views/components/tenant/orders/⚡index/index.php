<?php

use App\Models\AdminNotification;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TenantCoupon;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Commandes')] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    /** @var array{status: string} */
    public array $filters = ['status' => 'all'];

    public ?int $client_id = null;

    public ?string $due_at = null;

    public ?string $occasion = null;

    public ?string $notes = null;

    public int $deposit = 0;

    public int $discount = 0;

    public ?string $delivery_type = 'retrait';

    public ?string $delivery_address = null;

    public ?string $payment_method = null;

    public ?int $coupon_id = null;

    /** @var list<array{product_id: int, quantity: int, customization: ?array}> */
    public array $cart = [];

    public ?int $cart_product_id = null;

    public int $cart_quantity = 1;

    public ?string $cart_saveur = null;

    public ?string $cart_garniture = null;

    public ?string $cart_forme = null;

    public ?string $cart_taille = null;

    public ?int $cart_portions = null;

    public ?string $cart_design = null;

    public ?string $cart_couleur = null;

    public ?string $cart_thematique = null;

    public ?string $cart_allergies = null;

    public $cart_image = null;

    public int $cart_etages = 1;

    /** @var list<array{saveur: ?string, garniture: ?string, taille: ?string, forme: ?string, portions: ?int, faux: bool}> */
    public array $cart_etage_details = [['saveur' => null, 'garniture' => null, 'taille' => null, 'forme' => null, 'portions' => null, 'faux' => false]];

    public string $quick_client_name = '';

    public ?string $quick_client_phone = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::with(['client', 'items.product'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereHas('client', fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%'))
                    ->orWhere('occasion', 'ilike', '%'.$this->search.'%')
                    ->orWhere('notes', 'ilike', '%'.$this->search.'%');
            }))
            ->when($this->filters['status'] !== 'all', fn ($q) => $q->where('status', $this->filters['status']))
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function clients()
    {
        return Client::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function couponOptions()
    {
        return TenantCoupon::where('is_active', true)->get()->filter->isValid();
    }

    #[Computed]
    public function cartTotal(): int
    {
        $map = $this->products->keyBy('id');

        return collect($this->cart)->sum(function (array $line) use ($map): int {
            $product = $map->get($line['product_id']);

            return $product ? $product->price * $line['quantity'] : 0;
        });
    }

    public function updatedCartEtages(int $value): void
    {
        $value = max(1, min(5, $value));
        $this->cart_etages = $value;
        $current = count($this->cart_etage_details);
        if ($value > $current) {
            for ($i = $current; $i < $value; $i++) {
                $this->cart_etage_details[] = ['saveur' => null, 'garniture' => null, 'taille' => null, 'forme' => null, 'portions' => null, 'faux' => false];
            }
        } elseif ($value < $current) {
            $this->cart_etage_details = array_slice($this->cart_etage_details, 0, $value);
        }
    }

    public function addToCart(): void
    {
        $this->validate([
            'cart_product_id' => ['required', 'exists:products,id'],
            'cart_quantity' => ['required', 'integer', 'min:1'],
            'cart_saveur' => ['nullable', 'string', 'max:50'],
            'cart_garniture' => ['nullable', 'string', 'max:50'],
            'cart_forme' => ['nullable', 'string', 'max:30'],
            'cart_taille' => ['nullable', 'string', 'max:30'],
            'cart_portions' => ['nullable', 'integer', 'min:1'],
            'cart_design' => ['nullable', 'string', 'max:500'],
            'cart_couleur' => ['nullable', 'string', 'max:50'],
            'cart_thematique' => ['nullable', 'string', 'max:80'],
            'cart_allergies' => ['nullable', 'string', 'max:300'],
            'cart_image' => ['nullable', 'image', 'max:2048'],
            'cart_etages' => ['required', 'integer', 'min:1', 'max:5'],
            'cart_etage_details.*.saveur' => ['nullable', 'string', 'max:50'],
            'cart_etage_details.*.garniture' => ['nullable', 'string', 'max:50'],
            'cart_etage_details.*.taille' => ['nullable', 'string', 'max:30'],
            'cart_etage_details.*.forme' => ['nullable', 'string', 'max:30'],
            'cart_etage_details.*.portions' => ['nullable', 'integer', 'min:1'],
            'cart_etage_details.*.faux' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($this->cart_image) {
            $imagePath = $this->cart_image->store('order-customizations', 'public');
        }

        $customization = array_filter([
            'saveur' => $this->cart_saveur,
            'garniture' => $this->cart_garniture,
            'forme' => $this->cart_forme,
            'taille' => $this->cart_taille,
            'portions' => $this->cart_portions,
            'design' => $this->cart_design,
            'couleur' => $this->cart_couleur,
            'thematique' => $this->cart_thematique,
            'allergies' => $this->cart_allergies,
            'image' => $imagePath,
            'etages' => $this->cart_etages > 1 ? array_values(array_filter($this->cart_etage_details, fn ($e) => collect($e)->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->isNotEmpty())) : null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        $this->cart[] = ['product_id' => $this->cart_product_id, 'quantity' => $this->cart_quantity, 'customization' => $customization ?: null];
        $this->reset(['cart_product_id', 'cart_quantity', 'cart_saveur', 'cart_garniture', 'cart_forme', 'cart_taille', 'cart_portions', 'cart_design', 'cart_couleur', 'cart_thematique', 'cart_allergies', 'cart_image', 'cart_etages', 'cart_etage_details']);
        $this->cart_quantity = 1;
        $this->cart_etages = 1;
        $this->cart_etage_details = [['saveur' => null, 'garniture' => null, 'taille' => null, 'forme' => null, 'portions' => null, 'faux' => false]];
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function create(): void
    {
        $tenant = tenant();
        if ($tenant) {
            $limit = $tenant->featureLimit('orders.max');
            if ($limit !== null && Order::whereMonth('created_at', now()->month)->count() >= $limit) {
                Flux::toast(__('Limite commandes atteinte : ').$limit, variant: 'danger');

                return;
            }
        }

        $this->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'cart' => ['required', 'array', 'min:1'],
            'due_at' => ['nullable', 'date'],
            'occasion' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'delivery_type' => ['nullable', 'in:retrait,livraison'],
            'delivery_address' => ['nullable', 'string', 'max:300'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'coupon_id' => ['nullable', 'exists:tenant_coupons,id'],
        ]);

        DB::transaction(function () {
            $subtotal = $this->cartTotal;
            $discount = (int) ($this->discount ?? 0);

            if ($this->coupon_id) {
                $coupon = TenantCoupon::findOrFail($this->coupon_id);
                abort_unless($coupon->isValid(), 422, 'Coupon invalide');
                $discount = $coupon->type === 'percent' ? (int) round($subtotal * $coupon->value / 100) : $coupon->value;
                $discount = min($discount, $subtotal);
                $coupon->increment('used_count');
            }

            $deposit = (int) ($this->deposit ?? 0);
            $total = max(0, $subtotal - $discount);
            $order = Order::create([
                'client_id' => $this->client_id,
                'created_by' => auth()->id(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'deposit' => $deposit,
                'balance' => max(0, $total - $deposit),
                'due_at' => $this->due_at ? Carbon::parse($this->due_at) : null,
                'occasion' => $this->occasion,
                'notes' => $this->notes,
                'delivery_type' => $this->delivery_type,
                'delivery_address' => $this->delivery_address,
                'payment_method' => $this->payment_method,
            ]);

            foreach ($this->cart as $line) {
                $product = Product::findOrFail($line['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $product->price,
                    'line_total' => $product->price * $line['quantity'],
                    'customization' => $line['customization'] ?? null,
                ]);
            }
        });

        $this->reset(['client_id', 'cart', 'cart_product_id', 'cart_quantity', 'cart_saveur', 'cart_garniture', 'cart_forme', 'cart_taille', 'cart_portions', 'cart_design', 'cart_couleur', 'cart_thematique', 'cart_allergies', 'cart_image', 'cart_etages', 'cart_etage_details', 'due_at', 'occasion', 'notes', 'deposit', 'discount', 'delivery_type', 'delivery_address', 'payment_method', 'coupon_id']);
        $this->delivery_type = 'retrait';
        $this->cart_quantity = 1;
        Flux::modal('create-order')->close();
        Flux::toast(__('Commande créée.'));
    }

    public function createQuickClient(): void
    {
        $validated = $this->validate([
            'quick_client_name' => ['required', 'string', 'max:120'],
            'quick_client_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $client = Client::create([
            'name' => $validated['quick_client_name'],
            'phone' => $validated['quick_client_phone'],
        ]);

        $this->client_id = $client->id;
        $this->reset(['quick_client_name', 'quick_client_phone']);
        Flux::modal('quick-create-client')->close();
        Flux::toast(__('Client créé et sélectionné.'));
    }

    public const STATUS_FLOW = ['pending', 'confirmed', 'in_production', 'ready', 'delivered'];

    public ?int $deliverOrderId = null;

    public $delivery_photo = null;

    public ?string $delivery_signature = null;

    public function advanceStatus(int $id): void
    {
        $order = Order::findOrFail($id);
        $idx = array_search($order->status, self::STATUS_FLOW, true);
        if ($idx === false || $idx === count(self::STATUS_FLOW) - 1) {
            return;
        }
        $next = self::STATUS_FLOW[$idx + 1];

        if ($next === 'delivered') {
            $this->deliverOrderId = $id;
            $this->delivery_photo = null;
            $this->delivery_signature = null;
            Flux::modal('deliver-order')->show();

            return;
        }

        $order->update(['status' => $next]);
        Flux::toast(__('Statut : ').$next);

        if ($next === 'ready') {
            $tenant = tenant();
            AdminNotification::create([
                'title' => 'Commande prête — '.$tenant?->name,
                'body' => 'Commande #'.$order->id.($order->client ? ' — '.$order->client->name : '').' · '.($order->occasion ?? '').' · Échéance '.($order->due_at?->format('d/m H:i') ?? '—'),
                'type' => 'warning',
                'link' => route('admin.tenants.show', $tenant?->id ?? ''),
            ]);
        }
    }

    public function confirmDelivery(): void
    {
        $this->validate(['delivery_photo' => ['nullable', 'image', 'max:4096'], 'delivery_signature' => ['nullable', 'string', 'max:20000']]);

        $order = Order::findOrFail($this->deliverOrderId);
        $photoPath = null;
        if ($this->delivery_photo) {
            $photoPath = $this->delivery_photo->store('delivery-proofs', 'public');
        }

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'delivery_photo_path' => $photoPath,
            'delivery_signature' => $this->delivery_signature,
        ]);

        $tenant = tenant();
        AdminNotification::create([
            'title' => 'Commande livrée — '.$tenant?->name,
            'body' => 'Commande #'.$order->id.($order->client ? ' — '.$order->client->name : '').' · '.($order->occasion ?? ''),
            'type' => 'success',
            'link' => route('admin.tenants.show', $tenant?->id ?? ''),
        ]);

        Flux::modal('deliver-order')->close();
        $this->reset(['deliverOrderId', 'delivery_photo', 'delivery_signature']);
        Flux::toast(__('Livraison confirmée.'));
    }

    public function markPaid(int $id): void
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'paid', 'balance' => 0]);
        Flux::toast(__('Commande soldée.'));
    }

    public function cancel(int $id): void
    {
        Order::findOrFail($id)->update(['status' => 'cancelled']);
        Flux::toast(__('Commande annulée.'));
    }

    public ?int $remiseOrderId = null;

    public int $remiseDiscount = 0;

    public function applyDiscount(int $id, int $discount): void
    {
        $order = Order::findOrFail($id);
        $total = max(0, $order->subtotal - $discount);
        $order->update(['discount' => $discount, 'total' => $total, 'balance' => max(0, $total - $order->deposit)]);
        Flux::toast(__('Remise appliquée.'));
    }

    public function openRemise(int $id): void
    {
        $order = Order::findOrFail($id);
        $this->remiseOrderId = $id;
        $this->remiseDiscount = $order->discount;
        Flux::modal('remise-order')->show();
    }

    public function saveRemise(): void
    {
        $this->validate(['remiseDiscount' => ['required', 'integer', 'min:0']]);
        $this->applyDiscount($this->remiseOrderId, $this->remiseDiscount);
        Flux::modal('remise-order')->close();
    }
};
