<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserJoinController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'middleware' => ['guest', 'throttle:60,1']], function (Router $router) {

    $router->post('login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login');
    $router->post('forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->middleware(['check.email.config', 'throttle:5,1']);
    $router->post('confirm-password', [ConfirmPasswordController::class, 'confirmPassword'])->middleware('throttle:5,1');
    $router->post('user-join', [UserJoinController::class, 'join'])->middleware('throttle:5,1');
    $router->get('available-token/{token}', [UserJoinController::class, 'availableToken'])->middleware('throttle:20,1');

});
