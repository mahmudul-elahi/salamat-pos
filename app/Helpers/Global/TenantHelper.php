<?php

declare(strict_types=1);

use App\Models\Tenant\Tenant;

if (! function_exists('tenant')) {
    function tenant()
    {
        return Tenant::getCurrent();
    }
}
