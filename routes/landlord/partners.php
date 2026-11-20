<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Partners\B2bController;
use App\Http\Controllers\Pos\Partners\DealersController;
use App\Http\Controllers\Pos\Partners\FranchiseController;
use App\Http\Controllers\Pos\Partners\PartnerController;
use App\Http\Controllers\Pos\Partners\RetailersController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('dealers', DealersController::class);
    $router->apiResource('retailers', RetailersController::class);
    $router->apiResource('b2b', B2bController::class);
    $router->apiResource('franchises', FranchiseController::class);
    $router->apiResource('partners', PartnerController::class);
});
