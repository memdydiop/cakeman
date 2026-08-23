<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Initialise la tenancy pour toutes les requêtes `web` sur un sous-domaine
 * tenant (y compris le endpoint Livewire `livewire.update`, enregistré
 * globalement par le paquet avec `web` uniquement) : on délègue à
 * `InitializeTenancyByDomain` ; sur un domaine central (console, landing)
 * on laisse passer tel quel.
 */
class InitializeTenancyByDomainUnlessCentral
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
