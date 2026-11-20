<?php

namespace App\Http\Controllers\Administrator\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrator\Role\RoleRequest;
use App\Models\Role\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): LengthAwarePaginator
    {
        return Role::query()
//            ->withCount('users as total_users')
//            ->withCount('permissions as total_permissions')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $role = Role::query()->create($request->validated());

            if ($request->permissions) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            return successResponse('Role created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                return errorResponse('Role created has been failed', null, 500);
            }

            return errorResponse($exception->getMessage(), null, 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Role
    {
        return $role->load('permissions');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        try {
            DB::beginTransaction();
            $role->update($request->validated());

            if ($request->permissions) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            return successResponse('Role updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                return errorResponse('Role updated has been failed', null, 500);
            }

            return errorResponse($exception->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return successResponse('Role deleted successfully');
    }
}
