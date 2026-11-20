<?php

namespace App\Scopes;

use App\Exceptions\GeneralException;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{

    public function apply(Builder $builder, Model $model): Builder
    {
        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return $builder;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->isAppAdmin()) {
            return $builder;
        }

        $table = $model->getTable();

        // // Employees: filter by selected shop (X-domain) or all assigned shops
        // if ($user->is_employee) {
        //     $tenantIds = $user->tenants->pluck('id')->toArray();

        //     if (!empty($tenantIds)) {
        //         $selectedTenant = tenant();
        //         if ($selectedTenant && in_array($selectedTenant->id, $tenantIds)) {
        //             return $builder->where("{$table}.tenant_id", $selectedTenant->id);
        //         }

        //         return $builder->whereIn("{$table}.tenant_id", $tenantIds);
        //     }
        // }

        // Default: use X-domain based tenant
        $tenant = tenant();
        if (!$tenant && !request()->has('tenant_id')) {
            throw new GeneralException('Tenant not found');
        }

        //$tenantId = $tenant?->id ?? request('tenant_id');
        $tenantId = $tenant?->id;
        return $builder->where("{$table}.tenant_id", $tenantId);
    }
}
