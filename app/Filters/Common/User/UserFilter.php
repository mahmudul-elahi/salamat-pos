<?php

namespace App\Filters\Common\User;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends BaseFilter
{
    public function search($search = null)
    {
        return $this->builder->when($search, function (Builder $builder) use ($search) {
            $builder->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }


    // public function status($status = null): \Illuminate\Support\HigherOrderWhenProxy|Builder
    // {
    //     return $this->builder->when($status, fn(Builder $query) => $query->whereHas('userProfile', fn(Builder $query) => $query->where('status', $status)));
    // }

    public function role($id = null)
    {
        return $this->builder->when($id, fn(Builder $query) => $query->whereHas('roles', fn(Builder $query) => $query->where('role_id', $id)));
    }




}
