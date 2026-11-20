<?php

namespace App\Models\Pos\Page;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Pos\Seo\Seo;
use App\Models\Common\BaseModel;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends BaseModel
{
    use SoftDeletes, RevalidatesFrontendTrait;

    protected string $revalidationType = 'page-seo';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'tags',
        'tenant_id',
        'primary_logo',
        'secondary_logo',
        'shop_banner',
        'info_banner'
    ];


    public function seo(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Seo::class, 'model', 'model_type', 'model_id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
