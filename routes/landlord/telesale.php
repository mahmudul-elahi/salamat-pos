<?php

use App\Http\Controllers\Pos\Telesale\TelesaleController;
use App\Http\Controllers\Pos\Telesale\TelesaleResultController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    // Declared before the resource so "check-number" is not swallowed by
    // telesales/{telesale}. Named ".lists" so it resolves to the same
    // view_telesales permission as the listing rather than minting its own.
    $router->get('telesales/check-number', [TelesaleController::class, 'checkNumber'])
        ->name('telesales.lists');

    // Named ".edit" so it maps to the same update_telesales permission as the
    // full form rather than minting a permission for a single field.
    $router->patch('telesales/{telesale}/result', [TelesaleController::class, 'result'])
        ->name('telesales.edit');

    $router->apiResource('telesales', TelesaleController::class);
    $router->apiResource('telesale-results', TelesaleResultController::class);
});
