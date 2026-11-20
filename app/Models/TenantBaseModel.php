<?php

declare(strict_types=1);

namespace App\Models;

use App\Filters\BaseFilter;
use App\Models\Traits\Relationship\BelongToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantBaseModel extends Model
{
    use BelongToTenant;

    public function scopeFilters(Builder $query, BaseFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
