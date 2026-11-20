<?php

namespace App\Models\Ecommerce\Outlet;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletImage extends BaseModel
{
    protected $fillable = [
        'outlet_id',
        'image_url',
        'type',
        'alt_text',
        'sort_order',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
