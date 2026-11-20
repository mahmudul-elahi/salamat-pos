<?php

namespace App\Filters\Pos\Shop;

use App\Filters\BaseFilter;

class ShopDynamicTableFilter extends BaseFilter
{
    public function __construct(protected $table) {}

    public function shop($ids = null): void
    {
        $ids = explode(',', $ids);

        $this->builder->when($ids, fn ($query) => $query->whereIn($this->table.'.tenant_id', $ids));
    }
}
