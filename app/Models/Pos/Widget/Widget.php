<?php

declare(strict_types=1);

namespace App\Models\Pos\Widget;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use RevalidatesFrontendTrait;

    protected string $revalidationType = 'policies';

    protected string $revalidationSlugField = 'type';

    protected $fillable = [
        'name',
        'value',
        'type',
        'tenant_id',
    ];
}
