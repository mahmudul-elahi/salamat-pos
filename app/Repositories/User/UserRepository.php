<?php

namespace App\Repositories\User;

use App\Models\Role\Role;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getCachedAuthUserWithRoleAndPermissions(): ?User
    {
        $authUser = auth()->user();

        return $authUser->load('roles.permissions');
    }

    public function getAuthUserPermissions(): Collection
    {
        $user = $this->getCachedAuthUserWithRoleAndPermissions();

        return collect($user?->roles)
            ->flatMap(fn (Role $role) => $role->permissions ?? [])
            ->values();
    }

    public function findAuthUserPermission(string $permission)
    {

        $permissions = $this->getAuthUserPermissions();

        return $permissions->firstWhere('name', $permission);
    }

    public function getPermissionsForFrontEnd(): array
    {

        $permissions = $this->getAuthUserPermissions();

        return $permissions->mapWithKeys(fn ($permission) => [$permission['name'] => true])->toArray();
    }
}
