<?php

namespace App\Models\Pos\Blog;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (BlogTag $tag) {
            if (! $tag->slug) {
                $tag->slug = static::generateSlug($tag->name);
            }
        });

        static::updating(function (BlogTag $tag) {
            if ($tag->isDirty('name') && ! $tag->isDirty('slug')) {
                $tag->slug = static::generateSlug($tag->name, $tag->id);
            }
        });
    }

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $baseSlug = $slug ?: 'blog-tag';
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

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tags', 'tag_id', 'post_id');
    }
}
