<?php

namespace App\Models\Ecommerce\Faq;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FaqCategory extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'slug',
        'order',
        'status',
    ];

    #[\Override]
    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FaqCategory $category) {
            if (! $category->slug) {
                $category->slug = static::generateSlug($category->name);
            }
        });

        static::updating(function (FaqCategory $category) {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = static::generateSlug($category->name, $category->id);
            }
        });
    }

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug ?: 'faq-category';
        $candidate = $baseSlug;
        $count = 1;

        while (
            static::query()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $baseSlug.'-'.$count++;
        }

        return $candidate;
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('order');
    }
}
