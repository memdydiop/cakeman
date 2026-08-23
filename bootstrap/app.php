<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\CheckPlatformMaintenance;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\InitializeTenancyByDomainUnlessCentral;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.admin' => EnsureAdminAuthenticated::class,
            'admin.permission' => EnsureAdminPermission::class,
            'platform.maintenance' => CheckPlatformMaintenance::class,
            'api_token' => AuthenticateApiToken::class,
            'plan.feature' => EnsurePlanFeature::class,
        ]);

        $middleware->web(append: [
            // La route de mise à jour Livewire (`default-livewire.update`)
            // est enregistrée globalement par le paquet avec `web` uniquement :
            // sur un sous-domaine tenant, les requêtes AJAX Livewire
            // n'initialiseraient pas la tenancy et interrogeraient la base
            // centrale (42P01). On l'initialise donc pour tout le groupe `web`.
            InitializeTenancyByDomainUnlessCentral::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
