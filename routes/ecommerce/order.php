<?php

use App\Http\Controllers\Ecommerce\Order\OrderController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('orders', [OrderController::class, 'index'])
        ->name('orders.index');

    // $router->get('order-details/{order}', [OrderController::class, 'orderDetail'])
    //     ->name('order_details.index');

    $router->get('order-invoice-data/{order}', [OrderController::class, 'orderInvoiceData'])
        ->name('order_details.index');

    $router->get('orders/{order}', [OrderController::class, 'edit'])
        ->name('orders.edit');

    $router->patch('orders/{order}', [OrderController::class, 'update'])
        ->name('orders.update');

    $router->patch('orders/{order}/receive-amount', [OrderController::class, 'receiveAmount'])
        ->name('order_amount.received');

    $router->patch('orders/{order}/update-item-variant', [OrderController::class, 'updateItemVariant'])
        ->name('order_item_variant.update');

    $router->patch('orders/{order}/update-gift-item', [OrderController::class, 'updateGiftItem'])
        ->name('order_gift_item_variant.update');

    // POST, and declared above the catch-all PATCH orders/{order}/{action}
    // below so it can never be read as a status action.
    $router->post('orders/{order}/add-item', [OrderController::class, 'addItem'])
        ->name('order_items.create');

    $router->delete('orders/{order}/items/{itemId}', [OrderController::class, 'deleteItem'])
        ->name('order_items.destroy');

    $router->patch('orders/{order}/{action}', [OrderController::class, 'orderStatus'])
        ->name('order_status.update');

    $router->post('orders/order-status/{order}/approved', [OrderController::class, 'approveOrderWithSerials'])
        ->name('order_status.approved');

    $router->delete('orders/{order}', [OrderController::class, 'destroy'])
        ->name('orders.delete');

    $router->get('orders/sslcommerz/check-status', [OrderController::class, 'checkTransactionStatus'])
        ->name('orders_check_sslcommerz.status');
});
