<?php

use App\Http\Controllers\Pos\Courier\BdCourierController;
use App\Http\Controllers\Pos\Courier\CourierOrderController;
use App\Http\Controllers\Pos\Courier\PathaoController;
use App\Http\Controllers\Pos\Courier\RedxController;
use App\Http\Controllers\Pos\Courier\SteadfastController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('courier-orders', [CourierOrderController::class, 'index'])
        ->name('courier_orders.index');

    $router->get('pathao-orders', [PathaoController::class, 'index'])
        ->name('pathao_orders.index');

    $router->post('pathao-orders/sync', [PathaoController::class, 'syncOrders'])
        ->name('pathao_orders.sync');

    $router->get('pathao/stores', [PathaoController::class, 'stores'])
        ->name('pathao_stores.index');

    $router->get('pathao/cities', [PathaoController::class, 'cities'])
        ->name('pathao_cities.index');

    $router->get('pathao/cities/{cityId}/zones', [PathaoController::class, 'zones'])
        ->name('pathao_zones.index');

    $router->get('pathao/zones/{zoneId}/areas', [PathaoController::class, 'areas'])
        ->name('pathao_areas.index');

    $router->post('sales/{sale}/pathao-order', [PathaoController::class, 'storeOrder'])
        ->name('pathao_order.create');

    $router->get('bdcourier/check-fraud', [BdCourierController::class, 'checkFraud'])
        ->name('bdcourier_check_fraud.index');

    $router->get('bdcourier/check-fraud-api', [BdCourierController::class, 'checkFraudViaBdCourierApi'])
        ->name('bdcourier_check_fraud_api.index');

    $router->get('steadfast-orders', [SteadfastController::class, 'index'])
        ->name('steadfast_orders.index');

    $router->post('sales/{sale}/steadfast-order', [SteadfastController::class, 'storeOrder'])
        ->name('steadfast_order.create');

    $router->get('steadfast/districts', [SteadfastController::class, 'districts'])
        ->name('steadfast_districts.index');

    $router->get('steadfast/districts/{districtId}/thanas', [SteadfastController::class, 'thanas'])
        ->name('steadfast_thanas.index');

    $router->post('steadfast-orders/import', [SteadfastController::class, 'importOrders'])
        ->name('steadfast_orders.import');

    $router->get('steadfast/track/{consignmentId}', [SteadfastController::class, 'track'])
        ->name('steadfast_trackings.index');

    $router->get('pathao/track/{consignmentId}', [PathaoController::class, 'track'])
        ->name('pathao_trackings.index');

    $router->get('redx-orders', [RedxController::class, 'index'])
        ->name('redx_orders.index');

    $router->post('sales/{sale}/redx-order', [RedxController::class, 'storeOrder'])
        ->name('redx_order.create');

    // REDX has no separate district/thana lists — areas carry the district and
    // division names, so one endpoint feeds the whole address picker.
    $router->get('redx/areas', [RedxController::class, 'areas'])
        ->name('redx_areas.index');

    $router->get('redx/stores', [RedxController::class, 'stores'])
        ->name('redx_stores.index');

    $router->get('redx/track/{trackingId}', [RedxController::class, 'track'])
        ->name('redx_trackings.index');
});
