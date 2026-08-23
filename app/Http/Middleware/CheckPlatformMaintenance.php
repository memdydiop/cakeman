<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance de la landing publique (Module Configuration) : si activée,
 * les pages marketing (hors console, webhooks et onboarding) affichent une
 * page de maintenance avec le message configuré.
 */
class CheckPlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('platform.maintenance_enabled', false) === '1') {
            return response()->view('maintenance', [
                'message' => Setting::get('platform.maintenance_message', __('La plateforme est momentanément en maintenance. Revenez bientôt !')),
            ], 503);
        }

        return $next($request);
    }
}
