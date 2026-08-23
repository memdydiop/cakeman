<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement de la suspension (CdC § 5.1, Module 12) : après initialisation du
 * tenancy, un tenant non `active` (suspendu, annulé…) n'accède à aucun écran.
 * La fenêtre de maintenance (maintenance_until) bloque en 503.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized && tenant()?->status !== TenantStatus::Active) {
            abort(403, 'Votre boutique est suspendue. Contactez le support.');
        }

        if (tenancy()->initialized && tenant()?->isUnderMaintenance()) {
            abort(503, 'Votre boutique est en maintenance. Réessayez dans quelques minutes.');
        }

        return $next($request);
    }
}
