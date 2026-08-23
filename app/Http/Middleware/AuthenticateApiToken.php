<?php

namespace App\Http\Middleware;

use App\Models\AdminApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?? $request->header('X-API-Token');

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hash = hash('sha256', $plain);
        $token = AdminApiToken::where('token', $hash)->first();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->update(['last_used_at' => now()]);

        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
