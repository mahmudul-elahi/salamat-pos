<?php

namespace App\Services\Auth;

use App\Models\Role\Role;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Str;

class RegisterService extends BaseService
{
    protected object $user;

    protected string $password;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function checkSubscriber($request): ?object
    {
        return $this->getSubscriber($request->email);
    }

    public function register($request): static
    {
        $this->password = Str::random(8);

        $this->user = $this->model->query()->create([
            'email' => $request->email,
            'password' => $this->password,
            'is_admin' => 0,
        ]);

        return $this;
    }

    public function generatePassword($request): static
    {
        $this->password = Str::random(8);
        $this->user = $this->model->query()->updateOrCreate([
            'email' => $request->email,
        ], [
            'password' => $this->password,
            'is_admin' => 0,
        ]);

        return $this;
    }

    public function createProfile($request): static
    {
        $this->user->userProfile()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'status' => 'active',
        ]);

        return $this;
    }

    public function assignUserRole(): static
    {
        $role = $this->getRole();
        if ($role) {
            $this->user->roles()->attach($role->id);
        }

        return $this;
    }

    private function getRole(): ?object
    {
        return Role::query()
            ->where('alias', 'subscriber_admin')
            ->first();
    }
}
