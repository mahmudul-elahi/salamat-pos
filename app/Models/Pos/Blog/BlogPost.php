<?php

namespace App\Models\Pos\Blog;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends BaseModel
{
    use RevalidatesFrontendTrait;
    use SoftDeletes;

    protected string $revalidationType = 'blog';

    protected $fillable = [
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail',
        'banner',
        'reading_time',
        'is_featured',
        'is_trending',
        'is_popular',
        'is_pinned',
        'pinned_position',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_popular' => 'boolean',
        'is_pinned' => 'boolean',
        'pinned_position' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            if (! $post->slug) {
                $post->slug = static::generateSlug($post->title);
            }
        });

        static::updating(function (BlogPost $post) {
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $post->slug = static::generateSlug($post->title, $post->id);
            }
        });
    }

    public static function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $baseSlug = $slug ?: 'blog-post';
        $candidate = $baseSlug;
        $count = 1;

        while (
            static::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $baseSlug.'-'.$count++;
        }

        return $candidate;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public function viewLogs(): HasMany
    {
        return $this->hasMany(BlogPostView::class, 'post_id');
    }
}
