<?php

namespace App\Models\Tenant\PaymentMethod;

use App\Models\Common\BaseModel;
use App\Models\Setting\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PaymentMethod extends BaseModel
{
    use HasFactory;

    #[\Override]
    protected $fillable = [
        'name',
        'logo',
        'type',
        'tenant_id',
        'director_id',
    ];

    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    public function scopeForTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id', 'id');
    }
}
