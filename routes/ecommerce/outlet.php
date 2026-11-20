<?php

declare(strict_types=1);

use App\Http\Controllers\Ecommerce\Outlet\OutletController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {
    $router->apiResource('outlets', OutletController::class);
});
