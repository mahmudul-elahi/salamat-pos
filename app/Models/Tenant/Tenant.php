<?php

namespace App\Models\Tenant;

use App\Models\Setting\Setting;
use App\Models\Common\BaseModel;
use Illuminate\Support\Facades\Schema;
use App\Models\Common\User\UserProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends BaseModel
{
    protected $fillable = [
        'slug',
        'name',
        'is_ecommerce',
        'status',
    ];

    public $casts = [
        'is_ecommerce' => 'boolean',
    ];

    public static function findByDomain($param): Model|\Illuminate\Database\Eloquent\Builder|null
    {
        // Replace 'your_table_name' with the actual table name
        if (!Schema::hasTable((new static)->getTable())) {
            return null;
        }

        return self::query()->where('slug', $param)->first();
    }


    public function userProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserProfile::class, 'tenant_id', 'id');
    }


    public function settings(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    public function setting(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Setting::class, 'settingable');
    }

    // Handling tenant instance

    public function makeCurrent(): self
    {
        if (optional(static::getCurrent())->id == $this->id) {
            return $this;
        }

        static::forgetCurrent();
        app()->instance('currentTenant', $this);

        return $this;
    }

    public static function getCurrent()
    {
        if (!app()->has('currentTenant')) {
            return null;
        }

        return app('currentTenant');
    }

    public static function forgetCurrent()
    {
        $currentTenant = static::getCurrent();

        if (is_null($currentTenant)) {
            return null;
        }

        app()->forgetInstance('currentTenant');

        return $currentTenant;
    }

    // unique slug validation

    protected static function booted(): void
    {
        static::creating(function ($tenant) {
            $tenant->slug = static::generateUniqueSlug($tenant->name);
        });
    }

    protected static function generateUniqueSlug($title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;

        $count = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
