<?php

namespace App\Filters\Landlord\Company;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class CompanyFilter extends BaseFilter
{
    public function search($search = null)
    {
        return $this->builder->when($search, fn(Builder $builder) => $builder->where('name', 'LIKE', "%$search%"));
    }

    public function status($status = null)
    {
        return $this->builder->when($status, fn(Builder $builder) => $builder->where('status', $status));
    }
}
