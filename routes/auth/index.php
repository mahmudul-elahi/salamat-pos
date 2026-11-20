<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserJoinController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'middleware' => 'guest'], function (Router $router) {

    $router->post('login', [LoginController::class, 'login'])->name('login');
    $router->post('forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->middleware('check.email.config');
    $router->post('confirm-password', [ConfirmPasswordController::class, 'confirmPassword']);
    $router->post('user-join', [UserJoinController::class, 'join']);
    $router->get('available-token/{token}', [UserJoinController::class, 'availableToken']);

});
