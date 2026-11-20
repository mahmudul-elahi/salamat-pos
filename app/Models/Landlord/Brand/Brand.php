<?php

namespace App\Models\Landlord\Brand;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Pos\Description\LongDescription;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends BaseModel
{
    use SoftDeletes, RevalidatesFrontendTrait;

    protected string $revalidationType = 'brand';

    protected $fillable = [
        'name',
        'status',
        'slug',
        'tagline',
        'android_app',
        'ios_app',
        'primary_logo',
        'secondary_logo',
        'info_banner',
        'shop_banner',
        'description',
        'tags',
        'title',
        'order',
    ];

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }


    public function seos(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Seo::class, 'model');
    }

    public function longDescriptions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(LongDescription::class, 'model');
    }

    public function getLongDescriptionAttribute($tenantId = null)
    {
        $tenantId = $tenantId ?? request('tenant_id') ?? tenant()->id ?? null;

        $longDesc = $this->longDescriptions()
            ->where('tenant_id', $tenantId)
            ->first();

        return $longDesc?->description ?? $this->description;
    }

}
