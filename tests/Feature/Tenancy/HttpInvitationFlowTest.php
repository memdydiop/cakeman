<?php

use App\Enums\TenantStatus;
use App\Models\TenantUserInvitation;
use App\Models\User;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Str;
use Livewire\Mechanisms\HandleComponents\Checksum;

beforeEach(function () {
    (new TenantRolesSeeder)->run();
});

test('HTTP flow: accept page renders the form on the tenant subdomain', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);
    $token = Str::random(64);

    $tenant->run(function () use ($token): void {
        TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya-http@exemple.ci',
            'token' => $token,
            'expires_at' => now()->addHours(48),
        ]);
    });

    $response = $this->tenantGet($tenant, '/tenant-invitations/'.$token);

    $response->assertOk()
        ->assertSee('Activer ma boutique')
        ->assertSee('wire:snapshot');
});

test('HTTP flow: livewire update activates the tenant', function () {
    $tenant = $this->createTenant(['status' => TenantStatus::Pending]);
    $token = Str::random(64);

    $tenant->run(function () use ($token): void {
        TenantUserInvitation::create([
            'name' => 'Aya Kouassi',
            'email' => 'aya-http@exemple.ci',
            'token' => $token,
            'expires_at' => now()->addHours(48),
        ]);
    });

    $html = $this->tenantGet($tenant, '/tenant-invitations/'.$token)->getContent();

    preg_match('/wire:snapshot="([^"]+)"/', $html, $m);
    $snapshot = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    $snapshot['data']['password'] = 'mot-de-passe-test';
    $snapshot['data']['passwordConfirmation'] = 'mot-de-passe-test';
    unset($snapshot['checksum']);
    $snapshot['checksum'] = Checksum::generate($snapshot);

    $domain = $tenant->domains->first()->domain;

    $response = $this->withHeader('X-Livewire', 'true')
        ->withServerVariables(['HTTP_HOST' => $domain])
        ->postJson('http://'.$domain.'/livewire-cb67b112/update', [
            'components' => [[
                'snapshot' => json_encode($snapshot),
                'updates' => [],
                'calls' => [['path' => '', 'method' => 'accept', 'params' => []]],
                'locale' => 'en',
            ]],
        ]);

    $response->assertOk();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);

    $tenant->run(function () use ($token): void {
        expect(User::where('email', 'aya-http@exemple.ci')->exists())->toBeTrue()
            ->and(TenantUserInvitation::where('token', $token)->firstOrFail()->accepted_at)->not->toBeNull();
    });
});
