<?php

namespace App\Http\Controllers\Landlord\Role;

use App\Http\Controllers\Controller;
use App\Models\Role\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DetachUserRoleController extends Controller
{
    /**
     * @throws Exception
     */
    public function detach(Request $request, User $user): JsonResponse
    {
        if ($user->is_admin) {
            throw new Exception("Supper admin user can't be deleted");
        }

        if ($user->id == auth()->id()) {
            throw new Exception("Can't delete own account");
        }
        $user->roles()->detach();

        return successResponse('User role has been removed');

    }

    /**
     * @throws Exception
     */
    public function attach(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'users' => ['required', 'array', 'min:1'],
        ]);

        $this->previousRoleDetach($request);

        $role->users()->attach($request->users);

        return successResponse('User role has been attach');

    }

    /**
     * @throws Exception
     */
    private function previousRoleDetach($request): void
    {
        $users = User::query()->whereIn('id', $request->users)->get();
        $checkIsAdmin = $users->filter(fn ($user) => $user->is_admin)->first();
        if ($checkIsAdmin) {
            throw new Exception("Supper admin user can't be deleted");
        }

        $checkOwnAccount = $users->filter(fn ($user) => $user->id == auth()->id())->first();
        if ($checkOwnAccount) {
            throw new Exception("Can't delete own account");
        }

        $users->each(function ($item) {
            $item->roles()->detach();
        });
    }
}
