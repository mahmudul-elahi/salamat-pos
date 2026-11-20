<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthorizeException;
use App\Exceptions\GeneralException;
use App\Http\Middleware\Traits\RouteToPermissionStringTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    use RouteToPermissionStringTrait;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizeException
     * @throws GeneralException
     */
    public function handle(Request $request, Closure $next)
    {
        // Remove specific prefixes from the route name to standardize it
        $routeName = preg_replace('/core\.|app\.|hrm\./', '', $request->route()->getName(), 1);

        // Ensure the route has a valid name
        if (! $routeName) {
            throw new GeneralException('Route must have a name');
        }

        // If the user is an app admin, bypass further permission checks
        if (auth()->user()->isAppAdmin()) {
            return $next($request);
        }

        // Generate the permission string based on the route name
        $permissionName = $this->setRouteName($routeName)
            ->validateRouteName()
            ->getPermissionString();

        // dd($permissionName);

        /**
         * Authorize the user with the specified permission and determine allowed resources.
         * - If 'allowed_resource' is an empty array, all resources are permitted.
         * - Otherwise, only the resources in the 'allowed_resource' array are permitted.
         * - 'allowed_resource' will contain the IDs of models that the current user is authorized to access.
         */
        $meta = get_authorized($permissionName);

        // Merge the authorized resources into the request
        $request->merge(['allowed_resource' => $meta]);

        return $next($request);
    }
}
