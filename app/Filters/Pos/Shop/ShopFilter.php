<?php

namespace App\Filters\Pos\Shop;

use App\Filters\BaseFilter;

class ShopFilter extends BaseFilter
{
    public function shop($ids = null): void
    {
        $ids = explode(',', $ids);

        $this->builder->when($ids, fn ($query) => $query->whereIn('tenant_id', $ids));
    }
}
