<?php

namespace App\Domain;

use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;

abstract class TenantFinder
{
    abstract public function findForRequest(Request $request): ?Tenant;
}
