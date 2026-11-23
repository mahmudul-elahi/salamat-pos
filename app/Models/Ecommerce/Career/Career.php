<?php

namespace App\Models\Ecommerce\Career;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Career extends BaseModel
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'heading',
        'slug',
        'vacancies',
        'type',
        'salary',
        'image',
        'qualification',
        'deadline',
        'description',
        'benefits',
        'keywords',
        'status',
    ];

    /**
     * Boot method for auto slug
     */
    protected static function booted(): void
    {
        static::creating(function ($career) {
            $career->slug = static::generateSlug($career->heading);
        });

        static::updating(function ($career) {
            // optional: only update slug if heading changed
            if ($career->isDirty('heading')) {
                $career->slug = static::generateSlug($career->heading, $career->id);
            }
        });
    }

    /**
     * Generate unique slug
     */
    protected static function generateSlug($heading, $ignoreId = null)
    {
        $slug = Str::slug($heading);

        $originalSlug = $slug;
        $count = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->withTrashed()
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }
}
