<?php

declare(strict_types=1);

namespace App\Domain;

use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;

abstract class TenantFinder
{
    abstract public function findForRequest(Request $request): ?Tenant;
}
