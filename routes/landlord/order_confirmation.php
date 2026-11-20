<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\OrderConfirmation\OrderConfirmationController;
use App\Http\Controllers\Pos\OrderConfirmation\OrderConfirmationOptionController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    // Named ".edit" so it resolves to the same update_order_confirmations
    // permission as the full form rather than minting one for a single field.
    $router->patch('order-confirmations/{orderConfirmation}/confirm', [OrderConfirmationController::class, 'confirm'])
        ->name('order-confirmations.edit');

    // Route names must stay unique — `route:cache` refuses to build otherwise —
    // so the delivery leg carries its own name, and with it its own permission.
    // That is useful in its own right: the courier desk can be allowed to
    // update tracking without being able to rewrite prices.
    $router->patch('order-confirmations/{orderConfirmation}/delivery', [OrderConfirmationController::class, 'delivery'])
        ->name('order-confirmation-deliveries.update');

    $router->apiResource('order-confirmations', OrderConfirmationController::class);
    $router->apiResource('order-confirmation-options', OrderConfirmationOptionController::class);
});
