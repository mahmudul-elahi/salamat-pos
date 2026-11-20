<?php

namespace App\Filters\Pos\Report;

use App\Filters\BaseFilter;

class ReportNameFilter extends BaseFilter
{
    protected string $column;

    public function __construct(string $column = 'categories.name')
    {
        $this->column = $column;
    }

    public function search($search = null): \Illuminate\Database\Eloquent\Builder|\Illuminate\Support\HigherOrderWhenProxy
    {
        return $this->builder->when($search, fn($query) => $query->where($this->column, 'like', '%' . $search . '%')
        );
    }
}