<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Slider;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends BaseModel
{
    use RevalidatesFrontendTrait, SoftDeletes;

    protected string $revalidationType = 'slider';

    protected $fillable = [
        'image',
        'heading',
        'button_type',
        'banner_feature',
        'paragraph',
        'short_para',
        'url',
        'numbering',
        'status',
        'is_active',
        'tenant_id',
    ];

    public $casts = [
        'status' => 'integer',
        'button_type' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
