<?php

declare(strict_types=1);

use App\Http\Controllers\Coupon\CouponController;
use App\Http\Controllers\GiftProduct\GiftProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce'], function (Router $router) {

    $router->apiResource('coupons', CouponController::class);

    $router->patch('coupons/status-update/{coupon}', [CouponController::class, 'statusUpdate'])->name('coupons.status_update');

    $router->apiResource('gift-products', GiftProductController::class);

    $router->patch('gift-products/{giftProduct}/status', [GiftProductController::class, 'statusToggle'])
        ->name('gift-products.status');

    $router->get('gift-products-by-product/{productId}', [GiftProductController::class, 'getByProduct'])
        ->name('gift-products.by-product');

    $router->get('gift-products-by-pre-owned/{preOwnedId}', [GiftProductController::class, 'getByPreOwned'])
        ->name('gift-products.by-pre-owned');
});
