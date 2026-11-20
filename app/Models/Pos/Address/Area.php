<?php

namespace App\Models\Pos\Address;

use App\Models\Common\BaseModel;
use App\Models\Pos\Address\NewDistrict;

class Area extends BaseModel
{

    protected $guarded = [];

    public $casts = [
        'new_district_id' => 'integer'
    ];

    public function district(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }
}
