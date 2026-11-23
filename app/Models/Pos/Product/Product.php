<?php

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;
use App\Models\Landlord\Brand\Brand;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Description\LongDescription;
use App\Models\Pos\ProductSpecification\ProductSpecification;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Seo\Seo;
use App\Models\PreOwned\PreOwned;
use App\Models\PreOwned\PreOwnedProductSeo;
use App\Models\Product\GiftProduct;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends BaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    #[\Override]
    protected $fillable = [
        'category_id',
        'tax_id',
        'sub_category_id',
        'brand_id',
        'added_by',
        'product_name',
        'product_code',
        'a_status',
        'numbering',
        'slug',
        'sku',
        'stock',
        'short_description',
        'long_description',
        'weight',
        'dimensions',
        'materials',
        'other_info',
        'purchase_point',
        'product_image',
        'thumbnail_image',
        'booking_amount',
        'status',
        'is_ecommerce',
        'headline',
        'warranty_day',
        'warranty_text',
        'service_warranty',
        'has_official_warranty',
        'is_chinese',
        'is_unofficial',
        'is_refurbished',
        'tag',
        'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variant()
    {
        return $this->hasOne(ProductVariant::class);
    }

    public function getBasePriceAttribute()
    {
        $price = $this->variant?->price;

        return $price?->max_retail_price ?? null;
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function serial()
    {
        return $this->hasOne(ProductSerial::class, 'product_id', 'id');
    }

    public function purchasesItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getThumbnailImagesAttribute(): ?string
    {
        $thumbnailImage = $this->getFirstMedia('thumbnail_image');

        return $thumbnailImage?->getFullUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail_image');
    }

    public function barcodes()
    {
        return $this->hasMany(Barcode::class, 'product_id', 'id');
    }

    public function specificationItems()
    {
        return $this->hasMany(ProductSpecification::class)
            ->with(['parent', 'sub']);
    }

    public function getTotalStockAttribute()
    {
        $variantIds = $this->variants()->pluck('id');

        return VariantInventory::whereIn('product_variant_id', $variantIds)->sum('stock');
    }

    public function giftProducts(): HasMany
    {
        return $this->hasMany(GiftProduct::class, 'product_id', 'id');
    }

    public function cares(): HasMany
    {
        return $this->hasMany(ProductCare::class);
    }

    public function seos(): MorphMany
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

        $longDesc = $this->longDescriptions()
            ->where('tenant_id', $tenantId)
            ->first();

        return $longDesc?->description;
    }

    public function preOwneds()
    {
        return $this->hasMany(PreOwned::class, 'product_id', 'id');
    }

    public function preOwnedSeo()
    {
        return $this->hasOne(PreOwnedProductSeo::class, 'product_id', 'id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->where('is_primary', true);
    }
}
