<?php

namespace App\Models\Attribute;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends BaseModel
{
    use HasFactory;

    #[\Override]
    protected $fillable = [
        'name',
        'status',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
