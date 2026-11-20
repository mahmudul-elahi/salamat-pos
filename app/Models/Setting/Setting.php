<?php

namespace App\Models\Setting;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia, RevalidatesFrontendTrait;

    protected string $revalidationType = 'settings';

    protected $fillable = ['name', 'value', 'context', 'settingable_type', 'settingable_id'];

    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCompanyLogoAttribute(): string
    {
        $image = $this->getFirstMedia('company_logo');

        return $image?->getFullUrl();
    }

    public function getCompanyIconAttribute(): string
    {
        $image = $this->getFirstMedia('company_icon');

        return $image?->getFullUrl();
    }

    public function getCompanyBannerAttribute(): string
    {
        $image = $this->getFirstMedia('company_banner');

        return $image?->getFullUrl();
    }

    public function getShareAttribute(): string
    {
        $image = $this->getFirstMedia('share_icon');

        return $image?->getFullUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company_logo')
            ->singleFile();

        $this->addMediaCollection('shop_logo')
            ->singleFile();

        $this->addMediaCollection('company_secondary_logo')
            ->singleFile();

        $this->addMediaCollection('company_icon')
            ->singleFile();

        $this->addMediaCollection('company_banner')
            ->singleFile();

        $this->addMediaCollection('share_icon')
            ->singleFile();
    }
}
