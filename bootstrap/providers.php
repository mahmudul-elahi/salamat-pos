<?php

use App\Providers\AppServiceProvider;
use App\Providers\BroadcastServiceProvider;
use App\Providers\HelperServiceProvider;
use App\Providers\MacroServiceProvider;
use App\Providers\TenantServiceProvider;

return [
    AppServiceProvider::class,
    BroadcastServiceProvider::class,
    HelperServiceProvider::class,
    MacroServiceProvider::class,
    TenantServiceProvider::class,
];
