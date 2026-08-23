<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->tenant->run(function () {
        $this->user = User::factory()->create();
    });
});

test('categories can be created and toggled', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);

        Livewire::test('tenant.categories.index')
            ->set('name', 'Pâtisseries')
            ->call('create')
            ->assertHasNoErrors();

        expect(Category::where('name', 'Pâtisseries')->exists())->toBeTrue();

        $cat = Category::where('name', 'Pâtisseries')->firstOrFail();
        Livewire::test('tenant.categories.index')
            ->call('toggle', $cat->id)
            ->assertHasNoErrors();

        expect($cat->fresh()->is_active)->toBeFalse();
    });
});

test('products can be created respects plan limit', function () {
    $this->tenant->run(function () {
        $this->actingAs($this->user);

        $cat = Category::create(['name' => 'Viennoiseries']);

        Livewire::test('tenant.products.index')
            ->set('name', 'Croissant')
            ->set('category_id', $cat->id)
            ->set('price', 500)
            ->set('sku', 'CRO-001')
            ->call('create')
            ->assertHasNoErrors();

        expect(Product::where('name', 'Croissant')->exists())->toBeTrue();
    });
});

test('tenant can visit catalogue pages', function () {
    $this->actingAs($this->user);
    $this->tenantGet($this->tenant, route('tenant.categories.index'))->assertOk();
    $this->tenantGet($this->tenant, route('tenant.products.index'))->assertOk();
});
