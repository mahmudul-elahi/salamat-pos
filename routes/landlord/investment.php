<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Investent\InvestmentCategoryController;
use App\Http\Controllers\Pos\Investent\InvestmentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('investment-categories', InvestmentCategoryController::class);
    $router->apiResource('investments', InvestmentController::class);
});
