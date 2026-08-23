<?php

use App\Models\User;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->tenant->run(function () {
        $this->user = User::factory()->create();
    });
});

test('guests are redirected to the login page', function () {
    $response = $this->tenantGet($this->tenant, route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($this->user);

    $response = $this->tenantGet($this->tenant, route('dashboard'));

    $response->assertOk();
});
