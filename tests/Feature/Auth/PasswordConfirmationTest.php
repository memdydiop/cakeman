<?php

use App\Models\User;

beforeEach(function () {
    $this->tenant = $this->createTenant();
    tenancy()->initialize($this->tenant);
});

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user);
    $response = $this->tenantGet($this->tenant, route('password.confirm'));

    $response->assertOk();
});
