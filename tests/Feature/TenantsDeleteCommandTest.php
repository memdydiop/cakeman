<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

test('tenants:delete removes the tenant, its domains and its schema', function () {
    $tenant = $this->createTenant(domain: 'asupprimer.cakeman.test');

    $this->artisan('tenants:delete', ['tenant' => $tenant->id, '--force' => true])
        ->expectsOutputToContain('supprimé')
        ->assertSuccessful();

    expect(Tenant::find($tenant->id))->toBeNull();

    $this->assertDatabaseMissing('domains', ['domain' => 'asupprimer.cakeman.test']);

    $schemas = DB::select(
        'SELECT 1 FROM information_schema.schemata WHERE schema_name = ?',
        [$tenant->database()->getName()],
    );

    expect($schemas)->toBeEmpty();
});

test('tenants:delete rejects an unknown tenant', function () {
    $this->artisan('tenants:delete', ['tenant' => 'inconnu', '--force' => true])
        ->assertFailed();
});
