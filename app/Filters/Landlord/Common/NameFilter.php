<?php

namespace App\Filters\Landlord\Common;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class NameFilter extends BaseFilter
{
    public function search($search = null)
    {
        return $this->builder->when($search, fn(Builder $builder) => $builder->where('name', 'LIKE', "%$search%"));
    }
    
   public function shop($shopId = null): void
    {
        if (!$shopId) return;
        $this->builder->when($shopId, fn($query) => $query->where('tenant_id', $shopId));
    }

    public function status($status = null): void
    {
        if ($status === null || $status === '') return;

        $statuses = explode(',', $status);
        $this->builder->when($statuses, fn($query) => $query->whereIn('status', $statuses));
    }
}
