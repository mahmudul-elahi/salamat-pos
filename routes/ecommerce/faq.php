<?php

use App\Http\Controllers\Ecommerce\Faq\FaqCategoryController;
use App\Http\Controllers\Ecommerce\Faq\FaqController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {
    $router->get('faq-categories-active', [FaqCategoryController::class, 'allActive'])
        ->name('faq_categories.active');

    $router->apiResource('faq-categories', FaqCategoryController::class);
    $router->apiResource('faqs', FaqController::class);
});

