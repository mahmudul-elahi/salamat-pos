<?php

namespace App\Models\Attribute;

use App\Models\Attribute\AttributeValue;
use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'name',
        'status',
    ];

    public function values(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
