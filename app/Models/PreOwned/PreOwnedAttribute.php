<?php

namespace App\Models\PreOwned;

use Illuminate\Database\Eloquent\Model;

class PreOwnedAttribute extends Model
{
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
        return $this->belongsTo(\App\Models\Pos\Product\Attribute::class, 'attribute_id', 'id');
    }

    public function attributeValue()
    {
        return $this->belongsTo(\App\Models\Pos\Product\AttributeValue::class, 'attribute_value_id', 'id');
    }
}
