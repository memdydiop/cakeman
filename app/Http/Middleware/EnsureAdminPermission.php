<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement RBAC console (CdC § 2.3) : vérifie une permission Spatie du
 * guard `admin` — usage : `admin.permission:admin.tenant.view`.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin?->hasPermissionTo($permission, 'admin')) {
            abort(403);
        }

        return $next($request);
    }
}
