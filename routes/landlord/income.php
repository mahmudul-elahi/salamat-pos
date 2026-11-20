<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Income\IncomeCategoryController;
use App\Http\Controllers\Pos\Income\IncomeController;
use App\Http\Controllers\Pos\Purchase\PurchasePaymentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('income-categories', IncomeCategoryController::class);
    $router->apiResource('incomes', IncomeController::class);

    $router->post('income-payment/{income}', [PurchasePaymentController::class, 'incomePayment'])
        ->name('income_payment.create');
});
