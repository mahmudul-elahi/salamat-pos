<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Purchase\PurchaseController;
use App\Http\Controllers\Pos\Purchase\PurchaseDownloadController;
use App\Http\Controllers\Pos\Purchase\PurchasePaymentController;
use App\Http\Controllers\Pos\Purchase\PurchaseTransactionController;
use App\Http\Controllers\Pos\PurchaseReturn\PurchaseReturnController;
use App\Http\Controllers\Pos\PurchaseReturn\PurchaseReturnDownloadController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('purchases', PurchaseController::class);

    // $router->get('purchase-invoice-download/{purchase}', [PurchaseDownloadController::class, 'download'])
    //     ->name('purchase_details.download');

    $router->get('purchase-invoice-data/{purchase}', [PurchaseDownloadController::class, 'invoiceData'])
        ->name('purchase_details.download');

    $router->get('purchase-return-invoice-data/{purchaseReturn}', [PurchaseReturnDownloadController::class, 'invoiceData'])
        ->name('purchase_return_details.download');

    // $router->get('purchase-return-invoice-download/{purchaseReturn}', [PurchaseReturnDownloadController::class, 'download'])
    //     ->name('purchase_return_details.download');

    $router->apiResource('purchase-returns', PurchaseReturnController::class);

    $router->post('purchase-payment/{purchase}', [PurchasePaymentController::class, 'payment'])
        ->name('purchase_payment.create');

    $router->post('purchase-return-payment/{purchaseReturn}', [PurchasePaymentController::class, 'purchaseReturnPayment'])
        ->name('purchase_return_payment.create');

    $router->delete('purchase-return-transactions/{purchaseReturn}', [PurchaseReturnController::class, 'transactionDelete'])
        ->name('purchase_return_transactions.delete');

    $router->get('purchases/{purchase}/transactions', [PurchaseTransactionController::class, 'index'])
        ->name('purchase_transactions.index');
});
