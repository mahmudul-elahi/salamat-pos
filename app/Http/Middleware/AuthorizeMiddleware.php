<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\User\UserRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMiddleware
{
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = auth()->user();

        if ($authUser && $authUser->isAppAdmin()) {
            return $next($request);
        }
        $user = $this->userRepository->getCachedAuthUserWithRoleAndPermissions();

        if ($user instanceof User && $user->roles) {
            foreach ($user->roles as $role) {
                foreach ($role->permissions ?? [] as $permission) {
                    Gate::define($permission->name, fn (User $user) => true);
                }
            }
        }

        Gate::define('manage_dashboard', fn (User $user) => true);

        return $next($request);
    }
}
