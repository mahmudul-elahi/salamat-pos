<?php

namespace App\Models\Pos\Product;

use App\Models\Pos\Product\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttributeValue extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'attribute_id',
        'value',
        'status',
    ];

    public $casts = [
        'attribute_id' => 'integer',
    ];


    public function getFeaturedImageByProduct($productId)
    {
        $featuredImage = $this->media()
            ->where('collection_name', 'product_featured_image')
            ->where('custom_properties->product_id', $productId)
            ->first();

        return $featuredImage?->getFullUrl();
    }
    public function getSliderImagesByProduct($productId): \Illuminate\Support\Collection
    {
        return $this->media()
            ->where('collection_name', 'product_slider_images')
            ->where('custom_properties->product_id', $productId)
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->getUrl(),
                    'full_url' => $image->getFullUrl(),
                ];
            });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_featured_image');
        $this->addMediaCollection('product_slider_images');
    }

    public function attribute(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

}
