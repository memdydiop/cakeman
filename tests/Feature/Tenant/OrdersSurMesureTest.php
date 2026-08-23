<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->tenant->run(function () {
        $this->user = User::factory()->create();
    });
});

test('clients sur-mesure type and preferences', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);

        Livewire::test('tenant.clients.index')
            ->set('name', 'Mme Dupont')
            ->set('type', 'pro')
            ->set('phone', '0700000001')
            ->set('preferences', 'Sans arachide, vanille')
            ->set('birthday', '1990-05-12')
            ->call('create')
            ->assertHasNoErrors();

        $client = Client::where('name', 'Mme Dupont')->firstOrFail();
        expect($client->type)->toBe('pro');
        expect($client->preferences)->toBe('Sans arachide, vanille');
    });
});

test('orders multi-etages with faux gateau and customization', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);

        $cat = Category::create(['name' => 'Gâteaux']);
        $product = Product::create(['name' => 'Gâteau mariage', 'price' => 50000, 'category_id' => $cat->id]);
        $client = Client::create(['name' => 'Client Test']);

        Livewire::test('tenant.orders.index')
            ->set('client_id', $client->id)
            ->set('due_at', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->set('occasion', 'Mariage')
            ->set('deposit', 10000)
            ->set('delivery_type', 'livraison')
            ->set('cart_product_id', $product->id)
            ->set('cart_quantity', 1)
            ->set('cart_saveur', 'vanille')
            ->set('cart_garniture', 'ganache')
            ->set('cart_taille', '20cm')
            ->set('cart_portions', 20)
            ->set('cart_design', 'Joyeux mariage')
            ->set('cart_couleur', 'blanc/doré')
            ->set('cart_thematique', 'champêtre')
            ->set('cart_etages', 2)
            ->set('cart_etage_details.0.saveur', 'vanille')
            ->set('cart_etage_details.0.garniture', 'chantilly')
            ->set('cart_etage_details.0.taille', '20cm')
            ->set('cart_etage_details.0.forme', 'ronde')
            ->set('cart_etage_details.0.portions', 12)
            ->set('cart_etage_details.1.saveur', 'chocolat')
            ->set('cart_etage_details.1.garniture', 'ganache')
            ->set('cart_etage_details.1.taille', '15cm')
            ->set('cart_etage_details.1.faux', true)
            ->call('addToCart')
            ->assertHasNoErrors()
            ->call('create')
            ->assertHasNoErrors();

        $order = Order::latest()->firstOrFail();
        expect($order->occasion)->toBe('Mariage');
        expect($order->delivery_type)->toBe('livraison');
        expect($order->deposit)->toBe(10000);
        expect($order->balance)->toBe($order->total - 10000);

        $item = $order->items()->firstOrFail();
        $custom = $item->customization;
        expect($custom['saveur'])->toBe('vanille');
        expect($custom['etages'])->toHaveCount(2);
        expect($custom['etages'][1]['faux'])->toBeTrue();
        expect($custom['etages'][1]['saveur'])->toBe('chocolat');
    });
});

test('tenant dashboard wirecharts area renders', function () {
    $this->actingAs($this->user);
    $this->tenantGet($this->tenant, route('dashboard'))->assertOk();
});
