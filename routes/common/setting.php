<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\Setting\Email\EmailSettingController;
use App\Http\Controllers\Landlord\Setting\General\SettingController;
use App\Http\Controllers\Landlord\Setting\PaymentMethod\PaymentMethodController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'auth.permission']], function (Router $router) {
    $router->get('settings', [SettingController::class, 'index'])->name('settings.view');
    $router->get('email-settings', [EmailSettingController::class, 'index'])->name('email-settings.view');
    $router->post('email-settings', [EmailSettingController::class, 'update'])->name('email-settings.update');
    // $router->apiResource('/landlord/payment-methods', PaymentMethodController::class);
});
