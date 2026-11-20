<?php

namespace App\Http\Controllers\Landlord\User;

use App\Exceptions\GeneralException;
use App\Filters\Common\User\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\User\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected UserFilter $filter) {}

    public function index()
    {
        return User::query()
            ->filters($this->filter)
            ->where('is_employee', false)
            ->with(['tenants'])
            ->withWhereHas('roles', fn ($query) => $query->whereNull('alias'))
            ->latest()
            ->paginate(request('per_page', 10));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);

            if (! empty($data['role'])) {
                $user->roles()->sync($data['role']);
            }

            if (isset($data['tenant_id']) && $data['tenant_id']) {
                $user->tenants()->sync([$data['tenant_id']]);
            }

            DB::commit();

            return successResponse('User created successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to create user', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function show(User $user)
    {
        return $user->load(['tenants', 'roles']);
    }

    public function update(Request $request, User $user)
    {
        if ($request->has('roles')) {
            $user->roles()->sync($request->get('roles', []));

            return successResponse('User role updated successfully');
        }

        if ($request->has('tenant_id')) {
            $tenantId = $request->get('tenant_id');
            $user->tenants()->sync($tenantId ? [$tenantId] : []);
            $user->tokens()->delete();

            return successResponse('User shop updated successfully');
        }

        if ($request->has('status')) {
            $status = $request->get('status');
            $user->update(['status' => $status]);
            $user->tokens()->delete();

            return successResponse('User status updated successfully');
        }

        return successResponse('User updated successfully');
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->is_admin) {
            throw new GeneralException('Action not allowed');
        }

        if ($user->id == auth()->id()) {
            throw new GeneralException("Can't delete own account");
        }

        $user->delete();

        return successResponse('User deleted successfully');
    }
}
