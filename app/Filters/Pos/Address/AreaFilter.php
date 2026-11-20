<?php

namespace App\Filters\Pos\Address;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class AreaFilter extends BaseFilter
{
    public function search($search = null)
    {
        return $this->builder->when($search, fn(Builder $builder) => $builder->where(function (Builder $query) use ($search) {
            $query->where('name', 'LIKE', "%$search%")
                ->orWhere('thana', 'LIKE', "%$search%")
                ->orWhere('postcode', 'LIKE', "%$search%");
        }));
    }

    public function newDistrictId($districtId = null)
    {
        return $this->builder->when($districtId, fn(Builder $builder) => $builder->where('new_district_id', $districtId));
    }
}
