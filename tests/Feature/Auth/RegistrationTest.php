<?php

use Laravel\Fortify\Features;

test('fortify registration feature is disabled', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();
});

test('register route serves the tenant onboarding component', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('Créer votre boutique Cakeman');
});
