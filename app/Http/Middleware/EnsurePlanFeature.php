<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $tenant = tenant();

        if ($tenant === null) {
            abort(403, 'Fonctionnalité non disponible hors boutique.');
        }

        if (! $tenant->hasFeature($key)) {
            abort(403, "Fonctionnalité '{$key}' non incluse dans votre plan.");
        }

        return $next($request);
    }
}
