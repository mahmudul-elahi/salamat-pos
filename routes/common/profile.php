<?php

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\ProfilePasswordController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum']], function (Router $router) {

    $router->get('profile', [ProfileController::class, 'index']);
    $router->post('profile', [ProfileController::class, 'update']);
    $router->post('logout', [ProfileController::class, 'logout']);
    $router->patch('change-password', [ProfilePasswordController::class, 'password']);
    $router->post('change-avatar', [ProfileController::class, 'avatar']);
    $router->get('profile/timezones', [ProfileController::class, 'timezones']);
    $router->patch('profile/timezone', [ProfileController::class, 'updateTimezone']);

});
