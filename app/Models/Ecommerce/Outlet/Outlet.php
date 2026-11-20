<?php

namespace App\Models\Ecommerce\Outlet;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Outlet extends BaseModel
{
    use RevalidatesFrontendTrait;

    protected string $revalidationType = 'outlet';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'phone',
        'email',
        'address',
        'country_id',
        'new_district_id',
        'area_id',
        'google_map_url',
        'image',
        'opens',
        'closes',
        'close_day',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Outlet $outlet) {
            if (!$outlet->slug) {
                $outlet->slug = static::generateSlug($outlet->name);
            }
        });

        static::updating(function (Outlet $outlet) {
            if ($outlet->isDirty('name') && !$outlet->isDirty('slug')) {
                $outlet->slug = static::generateSlug($outlet->name, $outlet->id);
            }
        });
    }

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug ?: 'outlet';
        $candidate = $baseSlug;
        $count = 1;

        while (
            static::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $baseSlug . '-' . $count++;
        }

        return $candidate;
    }

    public function images(): HasMany
    {
        return $this->hasMany(OutletImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
