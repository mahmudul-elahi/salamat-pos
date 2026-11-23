<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttributeValue extends Model implements HasMedia
{
    use InteractsWithMedia;

    #[\Override]
    protected $fillable = [
        'attribute_id',
        'value',
        'status',
    ];

    #[\Override]
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

    public function getSliderImagesByProduct($productId): Collection
    {
        return $this->media()
            ->where('collection_name', 'product_slider_images')
            ->where('custom_properties->product_id', $productId)
            ->get()
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->getUrl(),
                'full_url' => $image->getFullUrl(),
            ]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_featured_image');
        $this->addMediaCollection('product_slider_images');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
