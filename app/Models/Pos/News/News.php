<?php

namespace App\Models\Pos\News;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Pos\Seo\Seo;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class News extends BaseModel
{
    use RevalidatesFrontendTrait;

    protected static $revalidationType = 'news';

    protected $fillable = [
        'heading',
        'name',
        'slug',
        'image',
        'description',
        'status',
    ];

    public function getDynamicSEOData(): SEOData
    {
        $seo = $this->seos()
            ->where(function ($q) {
                $q->where('tenant_id', tenant()->id)
                    ->orWhereNull('tenant_id');
            })
            ->orderByRaw('tenant_id IS NULL')
            ->first();

        $ogDescription = Str::limit(
            strip_tags($seo->description ?? $this->title),
            160
        );

        $ogImage = (! empty($seo?->image))
            ? asset($seo->image)
            : asset($this->primary_logo);

        $url = route('brand.products', $this->slug);

        return new SEOData(
            title: $seo->title ?? $this->title,
            description: $ogDescription,
            author: config('app.name'),
            image: $ogImage,
            url: $url,
            type: 'website'
        );
    }

    public function seos()
    {
        return $this->morphMany(Seo::class, 'model');
    }
}
