<?php

declare(strict_types=1);

namespace App\Filters\Landlord\Common;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class TitleFilter extends BaseFilter
{
    public function search($search = null)
    {
        return $this->builder->when($search, fn (Builder $builder) => $builder->where('title', 'LIKE', "%$search%"));
    }
}
