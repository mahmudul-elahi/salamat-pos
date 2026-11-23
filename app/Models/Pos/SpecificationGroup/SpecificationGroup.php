<?php

declare(strict_types=1);

namespace App\Models\Pos\SpecificationGroup;

use App\Models\Common\BaseModel;

class SpecificationGroup extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(SpecificationGroup::class, 'parent_id');
    }

    public function subSpecificationGroups()
    {
        return $this->hasMany(SpecificationGroup::class, 'parent_id', 'id');
    }
}
