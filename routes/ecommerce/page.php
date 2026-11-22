<?php

declare(strict_types=1);

use App\Http\Controllers\Ecommerce\PreOwned\PreOwnedProductSeoController;
use App\Http\Controllers\Ecommerce\Slider\SliderController;
use App\Http\Controllers\Pos\News\BlogController;
use App\Http\Controllers\Pos\News\NewsController;
use App\Http\Controllers\Pos\Page\PageBlockController;
use App\Http\Controllers\Pos\Page\PageController;
use App\Http\Controllers\Pos\Page\TekzoPageController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecommerce', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('pages', PageController::class);

    $router->get('all-pages', [PageController::class, 'allPage'])
        ->name('all_pages.view');

    $router->post('all-pages/{page}/update', [PageController::class, 'allPageUpdate'])
        ->name('all_pages.update');

    $router->get('pages/{page}/blocks', [PageBlockController::class, 'index'])
        ->name('page-blocks.view');

    $router->post('pages/{page}/blocks', [PageBlockController::class, 'saveTree'])
        ->name('page-blocks.update');

    $router->apiResource('news', NewsController::class);
    $router->apiResource('blogs', BlogController::class);
    $router->apiResource('sliders', SliderController::class);
    $router->patch('sliders/status-update/{slider}', [SliderController::class, 'statusUpdate'])
        ->name('sliders.status_update');

    /*
     * TEKZO website pages and SEO are website content, not shop data: access is
     * governed by permissions alone, so shop membership (tenant.check) is not required.
     */
    $router->withoutMiddleware('tenant.check')->group(function (Router $router): void {
        $router->apiResource('tekzo-pages', TekzoPageController::class);

        $router->get('pre-owned-product-seos', [PreOwnedProductSeoController::class, 'index'])
            ->name('pre_owned_product_seos.index');

        $router->get('pre-owned-product-seos/{product}', [PreOwnedProductSeoController::class, 'show'])
            ->name('pre_owned_product_seos.show');

        $router->patch('pre-owned-product-seos/{product}', [PreOwnedProductSeoController::class, 'update'])
            ->name('pre_owned_product_seos.update');
    });
});
