<?php

use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->tenant->run(function () {
        $this->user = User::factory()->create();
    });
});

test('suppliers can be created', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);
        Livewire::test('tenant.suppliers.index')
            ->set('name', 'Minoterie Abidjan')
            ->set('phone', '0701010101')
            ->call('create')
            ->assertHasNoErrors();

        expect(Supplier::where('name', 'Minoterie Abidjan')->exists())->toBeTrue();
    });
});

test('purchase orders can be created and received increments stock', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);

        $supplier = Supplier::create(['name' => 'Fournisseur Test']);
        $ingredient = Ingredient::create(['name' => 'Farine', 'unit' => 'kg', 'stock_quantity' => 10, 'unit_cost' => 1000]);

        Livewire::test('tenant.purchase-orders.index')
            ->set('supplier_id', $supplier->id)
            ->set('ingredient_id', $ingredient->id)
            ->set('quantity', 5)
            ->set('unit_cost', 1000)
            ->call('create')
            ->assertHasNoErrors();

        $po = PurchaseOrder::firstOrFail();
        expect($po->total_cost)->toBe(5000);

        Livewire::test('tenant.purchase-orders.index')
            ->call('receive', $po->id)
            ->assertHasNoErrors();

        expect($po->fresh()->status)->toBe('received');
        expect((float) $ingredient->fresh()->stock_quantity)->toBe(15.0);
    });
});

test('tenant can visit achats pages', function () {
    $this->actingAs($this->user);
    $this->tenantGet($this->tenant, route('tenant.suppliers.index'))->assertOk();
    $this->tenantGet($this->tenant, route('tenant.purchase-orders.index'))->assertOk();
});
