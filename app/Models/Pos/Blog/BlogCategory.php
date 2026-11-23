<?php

namespace App\Models\Pos\Blog;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'status',
    ];

    #[\Override]
    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BlogCategory $category) {
            if (! $category->slug) {
                $category->slug = static::generateSlug($category->name);
            }
        });

        static::updating(function (BlogCategory $category) {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = static::generateSlug($category->name, $category->id);
            }
        });
    }

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug ?: 'blog-category';
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

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }
}
