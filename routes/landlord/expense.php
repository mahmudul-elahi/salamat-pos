<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Pos\Expense\ExpenseController;
use App\Http\Controllers\Pos\Expense\ExpenseCategoryController;
use App\Http\Controllers\Pos\Purchase\PurchasePaymentController;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('expense-categories', ExpenseCategoryController::class);
    $router->apiResource('expenses', ExpenseController::class);


    $router->post('expense-payment/{expense}', [PurchasePaymentController::class, 'expensePayment'])
        ->name('expense_payment.create');

    $router->get('expenses/{expense}/voucher', [ExpenseController::class, 'voucherData'])
        ->name('expense_voucher.view');
});
