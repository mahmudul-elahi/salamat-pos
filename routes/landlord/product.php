<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\Ecommerce\SeoController;
use App\Http\Controllers\Pos\Attribute\AttributeController;
use App\Http\Controllers\Pos\Brand\BrandController;
use App\Http\Controllers\Pos\Category\CategoryController;
use App\Http\Controllers\Pos\Product\ProductBarcodeController;
use App\Http\Controllers\Pos\Product\ProductCareController;
use App\Http\Controllers\Pos\Product\ProductController;
use App\Http\Controllers\Pos\Product\ProductSpecificationController;
use App\Http\Controllers\Pos\Product\ProductSpecScrapeController;
use App\Http\Controllers\Pos\SpecificationGroup\SpecificationGroupController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('categories', CategoryController::class);
    $router->post('categories/update-order', [CategoryController::class, 'updateOrder'])->name('update_order.categories');
    $router->patch('categories/{category}/status', [CategoryController::class, 'toggleStatus'])
        ->name('categories.edit');
    $router->apiResource('attributes', AttributeController::class);
    $router->apiResource('specification-groups', SpecificationGroupController::class);
    $router->apiResource('brands', BrandController::class);
    $router->patch('brands/{brand}/status', [BrandController::class, 'toggleStatus'])
        ->name('brands.edit');
    $router->post('brands/update-order', [BrandController::class, 'updateOrder'])
        ->name('brands.edit');

    $router->apiResource('products', ProductController::class);

    $router->post('products/variant-update', [ProductController::class, 'updateVariant'])
        ->name('products_variant.update');

    $router->get('products/{product}/care', [ProductCareController::class, 'index'])
        ->name('products_care.index');

    $router->post('products/{product}/care', [ProductCareController::class, 'store'])
        ->name('products_care.create');

    $router->post('product-status/{product}', [ProductController::class, 'status'])
        ->name('product_status.update');

    $router->post('product-warranty/{product}', [ProductController::class, 'toggleOfficialWarranty'])
        ->name('product_warranty.update');

    $router->post('product-ecommerce/{product}', [ProductController::class, 'toggleEcommerce'])
        ->name('product_ecommerce.update');

    $router->post('product-chinese/{product}', [ProductController::class, 'toggleChinese'])
        ->name('product_chinese.update');

    $router->post('product-unofficial/{product}', [ProductController::class, 'toggleUnofficial'])
        ->name('product_unofficial.update');

    $router->post('product-refurbished/{product}', [ProductController::class, 'toggleRefurbished'])
        ->name('product_refurbished.update');

    $router->post('products/scrape-specs', [ProductSpecScrapeController::class, 'scrape'])
        ->name('product_specifications.scrape');

    $router->post('products/{product}/save-specs', [ProductSpecScrapeController::class, 'saveSpecs'])
        ->name('scrape_product_specifications.create');

    $router->get('products/{product}/specifications', [ProductSpecificationController::class, 'index'])
        ->name('product_specifications.index');

    $router->post('products/{product}/specifications', [ProductSpecificationController::class, 'store'])
        ->name('product_specifications.store');

    $router->put('products/{product}/specifications/{specification}', [ProductSpecificationController::class, 'update'])
        ->name('product_specifications.update');

    $router->delete('products/{product}/specifications/{specification}', [ProductSpecificationController::class, 'destroy'])
        ->name('product_specifications.destroy');

    /*
     * SEO is website content, not shop data: access is governed by the SEO
     * permissions alone, so shop membership (tenant.check) is not required.
     */
    $router->withoutMiddleware('tenant.check')->group(function (Router $router): void {
        $router->get('ecommerce/seo/{model}/{id}', [SeoController::class, 'show'])
            ->name('seo.show');

        $router->patch('ecommerce/seo/product/{id}', [SeoController::class, 'productSeoUpdate'])
            ->name('product_seo.update');

        $router->patch('ecommerce/seo/category/{id}', [SeoController::class, 'categorySeoUpdate'])
            ->name('category_seo.update');

        $router->patch('ecommerce/seo/brand/{id}', [SeoController::class, 'brandSeoUpdate'])
            ->name('brand_seo.update');
    });

    $router->post('product-barcode-generate', [ProductBarcodeController::class, 'barcodeGenerate'])
        ->name('barcode_generate.product');

    $router->get('product-barcodes', [ProductBarcodeController::class, 'barcodeList'])
        ->name('product_barcodes.view');

    $router->get('product-serial-history', [ProductBarcodeController::class, 'productSerialHistory'])
        ->name('product_serial_history.view');

    $router->post('product-print-barcodes', [ProductBarcodeController::class, 'printBarcode'])
        ->name('print_barcodes.product');

    $router->delete('product/barcodes/{barcode}', [ProductBarcodeController::class, 'destroy'])
        ->name('product_barcodes.delete');

    $router->get('barcode-generates', [ProductBarcodeController::class, 'generatedBarcode'])
        ->name('barcode_generates.index');
});

Route::group(['prefix' => 'landlord'], function (Router $router) {

    $router->get('products/variant-show/{product}', [ProductController::class, 'showVariant'])
        ->name('products_variant.show');

    $router->post('barcode-single-pdf', [ProductBarcodeController::class, 'barcodeSinglePdf'])
        ->name('barcode_single_pdf.product');

    $router->post('barcode-generated-pdf', [ProductBarcodeController::class, 'barcodeGeneratedPdf'])
        ->name('barcode_generated_pdf.product');

    $router->post('barcode-multi-pdf', [ProductBarcodeController::class, 'multiBarcodePdf'])
        ->name('barcode_multi_pdf.product');
});

// Gift Product
// Route::apiResource('gift-products', GiftProductController::class);
