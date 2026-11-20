<?php

declare(strict_types=1);

namespace App\Models\Common;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function scopeFilters(Builder $query, BaseFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
