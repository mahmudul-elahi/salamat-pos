<?php

namespace App\Models\Attribute;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'status',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
