<?php

use App\Http\Controllers\Pos\verify\ProductVerifyController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\Warranty\WarrantyController;
use App\Http\Controllers\Pos\Warranty\WarrantyClaimController;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('warranties', WarrantyController::class);
    $router->apiResource('product-verifies', ProductVerifyController::class);
    $router->patch('warranties/{warranty}/{action}', [WarrantyController::class, 'warrantyStatus'])
        ->name('warranties.status_update');
    $router->patch('product-verifies/{productVerify}/{action}', [ProductVerifyController::class, 'productVerifyStatus'])
        ->name('product_verifies.status_update');

    $router->get('warranty-claims', [WarrantyClaimController::class, 'index'])
        ->name('warranty_claims.index');
    $router->post('warranty-claims', [WarrantyClaimController::class, 'store'])
        ->name('warranty_claims.store');
    $router->patch('warranty-claims/{warrantyClaim}/{action}', [WarrantyClaimController::class, 'warrantyClaimStatus'])
        ->name('warranty_claims.status_update');
});
