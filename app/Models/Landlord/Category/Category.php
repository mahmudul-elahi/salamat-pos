<?php

namespace App\Models\Landlord\Category;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Pos\Description\LongDescription;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RalphJSmit\Laravel\SEO\Support\HasSEO;

class Category extends BaseModel
{
    use HasSEO, RevalidatesFrontendTrait, SoftDeletes;

    protected string $revalidationType = 'category';

    protected $fillable = [
        'parent_id',
        'name',
        'logo',
        'secondary_logo',
        'banner',
        'info_banner',
        'title',
        'status',
        'slug',
        'serial',
        'tags',
        'description',
        'order',
    ];

    public $casts = [
        'parent_id' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    public function childrenRecursive()
    {
        return $this->subCategories()->with('childrenRecursive');
    }

    /**
     * This category's id plus every descendant's id, however deep the tree
     * goes — so filtering by a parent category can pull in its children's
     * and grandchildren's products too. $visited guards against an
     * accidental parent_id cycle causing infinite recursion.
     */
    public function getAllDescendantIds(array &$visited = []): array
    {
        if (isset($visited[$this->id])) {
            return [];
        }
        $visited[$this->id] = true;

        $ids = [$this->id];

        foreach ($this->subCategories as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds($visited));
        }

        return $ids;
    }

    public function seos()
    {
        return $this->morphMany(Seo::class, 'model');
    }

    public function longDescriptions(): MorphMany
    {
        return $this->morphMany(LongDescription::class, 'model');
    }

    public function getLongDescriptionAttribute($tenantId = null)
    {
        $tenantId ??= request('tenant_id') ?? tenant()->id ?? null;
        if ($tenantId === 'null') {
            $tenantId = null;
        }

        $longDesc = $this->longDescriptions()
            ->where('tenant_id', $tenantId)
            ->first();

        return $longDesc?->description ?? $this->description;
    }
}
