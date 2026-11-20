<?php

namespace App\Models\Pos\Seo;

use Illuminate\Database\Eloquent\Model;

class Seo extends Model
{
    protected $table = 'seo';

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
        'title',
        'description',
        'long_description',
        'tags',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Seo is shared across many parent types (Page, Brand, PreOwned
     * product, ...) via the polymorphic "model" relation, so it has no
     * type/slug of its own to revalidate. Instead, whenever an Seo record
     * changes, delegate to whatever parent it belongs to — if that parent
     * uses RevalidatesFrontendTrait, this triggers its revalidation too,
     * with no per-controller wiring needed.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $seo) => $seo->revalidateParentFrontendCache());
        static::deleted(fn (self $seo) => $seo->revalidateParentFrontendCache());
    }

    protected function revalidateParentFrontendCache(): void
    {
        $parent = $this->model;

        if ($parent && method_exists($parent, 'revalidateFrontendCache')) {
            $parent->revalidateFrontendCache();
        }
    }
}
