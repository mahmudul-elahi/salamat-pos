<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\Transaction\TransactionController;




Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {


    $router->get('sale-transactions', [TransactionController::class, 'saleTransaction'])
        ->name('sale_transactions.view');

    $router->get('sale-return-transactions', [TransactionController::class, 'saleReturnTransaction'])
        ->name('sale_return_transactions.view');

    $router->get('purchase-transactions', [TransactionController::class, 'purchaseTransaction'])
        ->name('purchase_transactions.view');

    $router->get('purchase-return-transactions', [TransactionController::class, 'purchaseReturnTransaction'])
        ->name('purchase_return_transactions.view');

    $router->get('expense-transactions', [TransactionController::class, 'expenseTransaction'])
        ->name('expense_transactions.view');
});
