<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Care\CareController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {
    $router->apiResource('cares', CareController::class);
});
