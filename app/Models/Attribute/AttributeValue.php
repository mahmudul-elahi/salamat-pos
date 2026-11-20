<?php

namespace App\Models\Attribute;

use App\Models\Attribute\Attribute;
use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'status',
    ];

    public function attribute(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
