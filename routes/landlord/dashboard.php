<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Dashboard\DashboardController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos/dashboard', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('statistics', [DashboardController::class, 'statistic'])
        ->name('dashboard_statistics.view');

    $router->get('purchases-and-sales/{range_type?}', [DashboardController::class, 'purchasesAndSales'])
        ->name('dashboard_purchases_and_sales_graph.view');

    $router->get('top-sell-products', [DashboardController::class, 'topSellProduct'])
        ->name('dashboard_top_sell_products_graph.view');

    $router->get('top-sell-customers', [DashboardController::class, 'topSellCustomer'])
        ->name('dashboard_top_sell_customer_graph.view');

    $router->get('payment-sent-and-received/{range_type?}', [DashboardController::class, 'paymentSentAndReceived'])
        ->name('dashboard_sent_and_received_graph.view');

    $router->get('purchase-sale-and-return-overviews/{range_type?}', [DashboardController::class, 'purchaseSaleAndReturnOverview'])
        ->name('dashboard_purchase_sale_and_return_overviews.view');

});
