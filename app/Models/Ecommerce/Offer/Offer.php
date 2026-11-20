<?php

namespace App\Models\Ecommerce\Offer;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Offer extends Model
{
    use RevalidatesFrontendTrait;

    protected string $revalidationType = 'offer';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'banner',
        'type',
        'start_at',
        'end_at',
        'priority',
        'is_active'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($offer) {
            $baseSlug = Str::slug($offer->title);
            $slug = $baseSlug;
            $i = 1;
            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i;
                $i++;
            }
            $offer->slug = $slug;
        });
    }





    public function products()
    {
        return $this->hasMany(OfferProduct::class);
    }
}
