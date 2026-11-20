<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\CashTransfer\CashTransferController;
use App\Http\Controllers\Pos\DamageProduct\DamageProductController;
use App\Http\Controllers\Pos\Stock\PurchasesStockController;
use App\Http\Controllers\Pos\StockTransfer\StockTransferController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos'], function (Router $router) {

    $router->get('purchase-stocks', [PurchasesStockController::class, 'purchaseStock'])
        ->name('purchase_stocks.index');

    $router->get('product-stock-calculations', [PurchasesStockController::class, 'stockCalculation'])
        ->name('product_stock_calculation.index');
});

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('stock-transfers', [StockTransferController::class, 'index'])
        ->name('stock_transfers.index');

    $router->post('stock-transfers', [StockTransferController::class, 'store'])
        ->name('stock_transfers.store');

    $router->get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])
        ->name('stock_transfers.show');

    $router->post('stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve'])
        ->name('stock_transfers.approve');

    $router->post('stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])
        ->name('stock_transfers.cancel');

    $router->delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])
        ->name('stock_transfers.destroy');

    $router->get('cash-transfers', [CashTransferController::class, 'index'])
        ->name('cash_transfers.index');

    $router->post('cash-transfers', [CashTransferController::class, 'store'])
        ->name('cash_transfers.store');

    $router->get('cash-transfers/{cashTransfer}', [CashTransferController::class, 'show'])
        ->name('cash_transfers.show');

    $router->post('cash-transfers/{cashTransfer}/approve', [CashTransferController::class, 'approve'])
        ->name('cash_transfers.approve');

    $router->post('cash-transfers/{cashTransfer}/cancel', [CashTransferController::class, 'cancel'])
        ->name('cash_transfers.cancel');

    $router->delete('cash-transfers/{cashTransfer}', [CashTransferController::class, 'destroy'])
        ->name('cash_transfers.destroy');

    $router->get('damage-products', [DamageProductController::class, 'index'])
        ->name('damage_products.index');

    $router->post('damage-products', [DamageProductController::class, 'store'])
        ->name('damage_products.store');

    $router->get('damage-products/{damageProduct}', [DamageProductController::class, 'show'])
        ->name('damage_products.show');

    $router->post('damage-products/{damageProduct}/approve', [DamageProductController::class, 'approve'])
        ->name('damage_products.approve');

    $router->post('damage-products/{damageProduct}/cancel', [DamageProductController::class, 'cancel'])
        ->name('damage_products.cancel');

    $router->delete('damage-products/{damageProduct}', [DamageProductController::class, 'destroy'])
        ->name('damage_products.destroy');
});
