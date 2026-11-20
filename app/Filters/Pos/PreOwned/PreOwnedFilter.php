<?php

declare(strict_types=1);

namespace App\Filters\Pos\PreOwned;

use App\Filters\BaseFilter;

class PreOwnedFilter extends BaseFilter
{
    public function search($search = null)
    {
        $this->builder->when($search, function ($query) use ($search) {
            $query->where('serial_number', 'like', '%'.$search.'%')
                ->orWhereHas('product', function ($query) use ($search) {
                    $query->where('product_name', 'like', '%'.$search.'%');
                });
        });
    }

    /**
     * Frontend sends the literal words "available"/"sold" (not 0/1) as the
     * filter value, matching the dropdown option ids.
     */
    public function isSold($isSold = null): void
    {
        $this->builder->when(in_array($isSold, ['available', 'sold'], true), function ($query) use ($isSold) {
            $query->where('is_sold', $isSold === 'sold');
        });
    }
}
