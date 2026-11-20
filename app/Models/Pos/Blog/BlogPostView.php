<?php

namespace App\Models\Pos\Blog;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostView extends BaseModel
{
    protected $fillable = [
        'post_id',
        'ip',
        'device',
        'browser',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }
}
