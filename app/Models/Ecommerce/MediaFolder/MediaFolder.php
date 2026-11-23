<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\MediaFolder;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaFolder extends Model implements HasMedia
{
    use InteractsWithMedia;

    #[\Override]
    protected $fillable = ['name', 'parent_id',
        'id',
    ];

    #[\Override]
    public $casts = [
        'parent_id' => 'integer',
    ];

    public function children()
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('global_media');
    }
}
