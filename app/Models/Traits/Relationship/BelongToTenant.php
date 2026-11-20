<?php

namespace App\Models\Traits\Relationship;

use App\Exceptions\GeneralException;
use App\Models\Tenant\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongToTenant
{
    /**
     * Boot the BelongToTenant trait.
     *
     * @throws GeneralException
     */
    protected static function bootBelongToTenant(): void
    {
        if (! app()->runningInConsole()) {
            static::addGlobalScope(new TenantScope);

            // Apply tenant logic on creating and saving
            static::creating(fn ($model) => $model->assignTenantId());
            static::saving(fn ($model) => $model->assignTenantId());
            static::updating(fn ($model) => $model->assignTenantId());
        }
    }

    /**
     * Ensure tenant_id is assigned properly.
     *
     * @throws GeneralException
     */
    protected function assignTenantId(): void
    {

        if ($this->tenant_id) {
            return; // Already set, nothing to do
        }

        if (tenant()) {
            $this->tenant_id = tenant()->id;
        } elseif (request()->has('tenant_id')) {
            $this->tenant_id = request('tenant_id');
        } else {
            throw new GeneralException('The warehouse/store field is required.', 400);
        }

        $user = auth()->user();
        if ($user && ! $user->isAppAdmin() && ! $user->tenants()->whereKey($this->tenant_id)->exists()) {
            throw new GeneralException('Unauthorized shop access.', 403);
        }
    }

    /**
     * Relationship with Tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
