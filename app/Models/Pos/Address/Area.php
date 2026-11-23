<?php

namespace App\Models\Pos\Address;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends BaseModel
{
    #[\Override]
    protected $guarded = [];

    #[\Override]
    public $casts = [
        'new_district_id' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }
}
