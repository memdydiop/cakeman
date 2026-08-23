<?php

test('landing page renders the pitch and call to actions', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Créer ma boutique')
        ->assertSee('Gagnez 2 heures par jour')
        ->assertSee('Commandes sur WhatsApp, stocks sur papier')
        ->assertSee('Fonctionnalités')
        ->assertSee('Tarifs')
        ->assertSee('Des prix simples et justes')
        ->assertSee('5 000 FCFA')
        ->assertSee('Mobile Money')
        ->assertSee(route('register'));
});
