<?php

use App\Http\Controllers\Pos\Purchase\PurchasePaymentController;
use App\Http\Controllers\Pos\Sale\SaleController;
use App\Http\Controllers\Pos\Sale\SaleDownloadController;
use App\Http\Controllers\Pos\SaleReturn\SaleReturnController;
use App\Http\Controllers\Pos\SaleReturn\SaleReturnDownloadController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('sales', SaleController::class);
    $router->patch('sales/{sale}/update-seller', [SaleController::class, 'updateSeller'])
        ->name('sales.update_seller');

    $router->apiResource('sale-returns', SaleReturnController::class);

    $router->post('sale-payment/{sale}', [PurchasePaymentController::class, 'salePayment'])
        ->name('sale_payment.create');

    $router->post('sale-return-payment/{saleReturn}', [PurchasePaymentController::class, 'saleReturnPayment'])
        ->name('sale_return_payment.create');

    //  $router->get('sale-invoice-download/{sale}', [SaleDownloadController::class, 'download'])
    //      ->name('sale_details.download');

    $router->get('sale-invoice-data/{sale}', [SaleDownloadController::class, 'invoiceData'])
        ->name('sale_details.download');

    $router->get('sale-return-invoice-data/{saleReturn}', [SaleReturnDownloadController::class, 'invoiceData'])
        ->name('sale_return_details.download');

    // $router->get('sale-return-invoice-download/{saleReturn}', [SaleReturnDownloadController::class, 'download'])
    //     ->name('sale_return_details.download');
});
