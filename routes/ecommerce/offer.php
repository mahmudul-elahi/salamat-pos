<?php

declare(strict_types=1);

use App\Http\Controllers\Ecommerce\Offer\OfferController;
use App\Http\Controllers\Ecommerce\Offer\OfferProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('offers', OfferController::class);
    $router->apiResource('offer-products', OfferProductController::class);

});
