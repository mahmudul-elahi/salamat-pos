<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ecommerce\Offer\OfferController;
use App\Http\Controllers\Ecommerce\Offer\OfferProductController;
use Illuminate\Routing\Router;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('offers', OfferController::class);
    $router->apiResource('offer-products', OfferProductController::class);

});

