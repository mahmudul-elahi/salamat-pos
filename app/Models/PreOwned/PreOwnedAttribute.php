<?php

declare(strict_types=1);

namespace App\Models\PreOwned;

use App\Models\Pos\Product\Attribute;
use App\Models\Pos\Product\AttributeValue;
use Illuminate\Database\Eloquent\Model;

class PreOwnedAttribute extends Model
{
    #[\Override]
    protected $fillable = [
        'pre_owned_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public function preOwned()
    {
        return $this->belongsTo(PreOwned::class, 'pre_owned_id', 'id');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id', 'id');
    }
}
