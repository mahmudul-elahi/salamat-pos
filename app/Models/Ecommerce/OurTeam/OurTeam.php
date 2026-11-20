<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\OurTeam;

use App\Models\TenantBaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class OurTeam extends TenantBaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'about',
        'designation',
        'profile_photo',
        'tenant_id',
    ];
}
