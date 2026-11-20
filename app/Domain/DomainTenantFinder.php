<?php

namespace App\Domain;

use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $domain = $request->header('X-domain');

        return Tenant::findByDomain($domain);
    }
}
