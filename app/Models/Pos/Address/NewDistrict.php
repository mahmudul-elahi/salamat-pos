<?php

declare(strict_types=1);

namespace App\Models\Pos\Address;

use App\Models\Common\BaseModel;

class NewDistrict extends BaseModel
{
    #[\Override]
    protected $guarded = [];

    public function areas()
    {
        return $this->hasMany(Area::class, 'new_district_id');
    }
}
