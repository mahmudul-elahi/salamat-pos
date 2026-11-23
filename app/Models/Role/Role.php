<?php

namespace App\Models\Role;

use App\Models\Common\BaseModel;
use App\Models\Permission\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'alias',
        'is_admin',
        'is_default',
    ];

    #[\Override]
    public $casts = [
        'is_admin' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function isDefault()
    {
        return $this->is_default;
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public static function findByName(string $role_name)
    {
        return self::query()->whereName($role_name)->first();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeCustomerRole(Builder $query): Builder
    {
        return $query->where('alias', 'customer');
    }
}
