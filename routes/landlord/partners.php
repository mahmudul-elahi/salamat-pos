<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Pos\Partners\B2bController;
use App\Http\Controllers\Pos\Partners\DealersController;
use App\Http\Controllers\Pos\Partners\PartnerController;
use App\Http\Controllers\Pos\Partners\FranchiseController;
use App\Http\Controllers\Pos\Partners\RetailersController;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('dealers', DealersController::class);
    $router->apiResource('retailers', RetailersController::class);
    $router->apiResource('b2b', B2bController::class);
    $router->apiResource('franchises', FranchiseController::class);
    $router->apiResource('partners', PartnerController::class);
});
