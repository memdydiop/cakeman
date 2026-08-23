<?php

namespace Tests;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Features;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Tests\Concerns\RefreshTenantDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshTenantDatabase;

    /** @var array<int, callable> */
    protected array $teardownCallbacks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshTestDatabase();
        $this->purgeCentralTables();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        foreach ($this->teardownCallbacks as $callback) {
            $callback();
        }

        URL::forceRootUrl(null);

        parent::tearDown();
    }

    protected function registerTeardown(callable $callback): void
    {
        $this->teardownCallbacks[] = $callback;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Crée un tenant (pipeline synchrone : schéma + migrations + rôles + settings)
     * et programme le nettoyage du schéma en fin de test — RefreshDatabase
     * (transaction) laisse les schémas `tenant_*` orphelins (DDL non transactionnel).
     */
    protected function createTenant(array $attributes = [], ?string $domain = null): TenantWithDatabase
    {
        $tenant = Tenant::create(array_merge([
            'name' => 'Pâtisserie Test',
            'status' => TenantStatus::Active,
        ], $attributes));

        $tenant->domains()->create([
            'domain' => $domain ?? Str::random(12).'.cakeman.test',
        ]);

        $this->registerTeardown(function () use ($tenant): void {
            if (tenancy()->initialized) {
                tenancy()->end();
            }

            $tenant->fresh()?->delete();
            DB::statement('DROP SCHEMA IF EXISTS "'.$tenant->database()->getName().'" CASCADE');
        });

        return $tenant;
    }

    /**
     * Requête HTTP vers une route du groupe tenancy (routes/tenant.php).
     * Le host doit venir du domaine du tenant : `app.url` est réorienté vers le
     * tenant, car `prepareUrlForRequest` reconstruit l'URL absolue à partir de
     * `app.url` (le header Host seul est écrasé par Symfony).
     */
    protected function tenantRequest(TenantWithDatabase $tenant, string $method, string $uri, array $data = []): TestResponse
    {
        tenancy()->initialize($tenant);

        $domain = $tenant->domains->firstOrFail()->domain;

        // Miroir de la prod : le guard par défaut reste `web` — sinon un
        // `actingAs($x, 'admin')` antérieur ferait authentifier les requêtes
        // tenant par le guard admin (fuite de session entre consoles).
        Auth::shouldUse('web');

        URL::forceRootUrl('http://'.$domain);

        $path = (string) parse_url($uri, PHP_URL_PATH);

        if ($query = parse_url($uri, PHP_URL_QUERY)) {
            $path .= '?'.$query;
        }

        return $this
            ->withServerVariables(['HTTP_HOST' => $domain])
            ->{$method}($path, $data);
    }

    protected function tenantGet(TenantWithDatabase $tenant, string $uri): TestResponse
    {
        return $this->tenantRequest($tenant, 'get', $uri);
    }

    protected function tenantPost(TenantWithDatabase $tenant, string $uri, array $data = []): TestResponse
    {
        return $this->tenantRequest($tenant, 'post', $uri, $data);
    }
}
