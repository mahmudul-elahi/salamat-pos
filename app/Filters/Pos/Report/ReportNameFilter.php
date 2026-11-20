<?php

namespace App\Filters\Pos\Report;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

class ReportNameFilter extends BaseFilter
{
    public function __construct(protected string $column = 'categories.name') {}

    public function search($search = null): Builder|HigherOrderWhenProxy
    {
        return $this->builder->when($search, fn ($query) => $query->where($this->column, 'like', '%'.$search.'%')
        );
    }
}
