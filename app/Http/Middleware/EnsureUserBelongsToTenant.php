<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || $user->isAppAdmin()) {
            return $next($request);
        }

        $currentTenant = tenant();
        if ($currentTenant && ! $user->tenants()->whereKey($currentTenant->id)->exists()) {
            abort(403, 'Unauthorized shop access.');
        }

        if ($request->filled('tenant_id')) {
            $requestedTenantId = $request->input('tenant_id');
            if (is_numeric($requestedTenantId) && ! $user->tenants()->whereKey($requestedTenantId)->exists()) {
                abort(403, 'Unauthorized shop access.');
            }
        }

        return $next($request);
    }
}
