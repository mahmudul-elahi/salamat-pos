<?php

declare(strict_types=1);

use App\Http\Controllers\Ecommerce\Career\CareerController;
use App\Http\Controllers\Ecommerce\Elite\EliteController;
use App\Http\Controllers\Ecommerce\Emi\EmiBankController;
use App\Http\Controllers\Ecommerce\OurTeam\OurTeamController;
use App\Http\Controllers\Ecommerce\PopupDialog\PopupDialogController;
use App\Http\Controllers\Ecommerce\PreOwned\PreOwnedCareController;
use App\Http\Controllers\Ecommerce\PreOwned\PreOwnedProductController;
use App\Http\Controllers\Ecommerce\Reels\ReelsVideoController;
use App\Http\Controllers\Ecommerce\Review\ReviewController;
use App\Http\Controllers\Ecommerce\SellDeviceRequest\SellDeviceRequestController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('reviews', ReviewController::class);
    $router->apiResource('our-teams', OurTeamController::class);
    $router->apiResource('careers', CareerController::class);

    $router->post('careers/status/{career}', [CareerController::class, 'updateStatus'])
        ->name('career_status.update');

    $router->get('applicants', [CareerController::class, 'applicants'])
        ->name('career_applicants.index');

    $router->get('applicants/{applicant}', [CareerController::class, 'showApplicant'])
        ->name('career_applicants.show');

    $router->apiResource('emi-banks', EmiBankController::class);

    $router->apiResource('reels-videos', ReelsVideoController::class);

    $router->apiResource('elites', EliteController::class);

    $router->apiResource('popup-dialogs', PopupDialogController::class);

    $router->apiResource('pre-owned-products', PreOwnedProductController::class);

    $router->patch('pre-owned-products/{preOwned}/status', [PreOwnedProductController::class, 'statusUpdate'])
        ->name('status_pre_owned_products.update');

    $router->patch('pre-owned-products/{preOwned}/price', [PreOwnedProductController::class, 'updatePrice'])
        ->name('price_pre_owned_products.update');

    $router->get('pre-owned-products/{preOwned}/care', [PreOwnedCareController::class, 'index'])
        ->name('pre_owned_products_care.index');

    $router->post('pre-owned-products/{preOwned}/care', [PreOwnedCareController::class, 'store'])
        ->name('pre_owned_products_care.store');

    $router->get('sell-device-requests', [SellDeviceRequestController::class, 'index'])
        ->name('sell_device_requests.index');

    $router->get('sell-device-requests/{sellDeviceRequest}', [SellDeviceRequestController::class, 'show'])
        ->name('sell_device_requests.show');

    $router->patch('sell-device-requests/{sellDeviceRequest}', [SellDeviceRequestController::class, 'update'])
        ->name('sell_device_requests.update');

    $router->delete('sell-device-requests/{sellDeviceRequest}', [SellDeviceRequestController::class, 'destroy'])
        ->name('sell_device_requests.destroy');
});
