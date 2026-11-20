<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\Setting\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Pos\Brand\BrandController;
use App\Http\Controllers\Pos\Customer\CustomerController;
use App\Http\Controllers\Pos\Expense\ExpenseCategoryController;
use App\Http\Controllers\Pos\OrderConfirmation\OrderConfirmationOptionController;
use App\Http\Controllers\Pos\Selected\SelectedController;
use App\Http\Controllers\Pos\Supplier\SupplierController;
use App\Http\Controllers\Pos\Telesale\TelesaleResultController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'selected'], function (Router $router) {

    $router->get('product-lists', [SelectedController::class, 'productList']);
    $router->get('categories', [SelectedController::class, 'category']);
    $router->get('nested-categories', [SelectedController::class, 'nestedCategories']);
    $router->get('specifications', [SelectedController::class, 'parentSpecification']);
    $router->get('all-specifications', [SelectedController::class, 'allSpecifications']);
    $router->get('all-specifications/{id}', [SelectedController::class, 'getSpecification']);
    $router->get('products', [SelectedController::class, 'product']);
    $router->get('products-variant', [SelectedController::class, 'productVariant']);
    $router->get('/{id}/products/variants', [SelectedController::class, 'productSku']);
    $router->get('products/variant/attributes/{id}', [SelectedController::class, 'productAttribute']);
    $router->get('product/variant-serial/{variantId}', [SelectedController::class, 'productVariantSerial']);
    $router->get('brands', [BrandController::class, 'index']);
    $router->get('income-categories', [SelectedController::class, 'incomeCategory']);
    $router->get('expense-categories', [ExpenseCategoryController::class, 'index']);
    $router->get('payment-methods', [PaymentMethodController::class, 'index']);
    $router->get('colors', [SelectedController::class, 'color']);
    $router->get('attributes', [SelectedController::class, 'attribute']);
    $router->get('attribute-values/{attributeId}', [SelectedController::class, 'attributeValue']);
    $router->get('countries', [SelectedController::class, 'country']);
    $router->get('contacts', [SelectedController::class, 'contact']);
    $router->get('districts', [SelectedController::class, 'district']);
    $router->get('/{id}/districts/areas', [SelectedController::class, 'area']);
    $router->get('suppliers', [SupplierController::class, 'supplierList']);
    $router->get('customers', [CustomerController::class, 'customerList']);
    $router->get('customer-levels', [SelectedController::class, 'customerLevel']);
    $router->get('shops', [SelectedController::class, 'shop']);
    $router->get('warehouses', [SelectedController::class, 'warehouse']);
    $router->get('ecommerces', [SelectedController::class, 'ecommerce']);
    $router->get('search-product', [SelectedController::class, 'searchProduct']);
    $router->get('purchase-search-product', [SelectedController::class, 'purchaseSearchProduct']);
    $router->get('purchase-return-search-product', [SelectedController::class, 'purchaseReturnSearchProduct']);
    $router->get('sale-search-product', [SelectedController::class, 'saleSearchProduct']);
    $router->get('sale-return-search-product', [SelectedController::class, 'saleReturnSearchProduct']);
    $router->get('investment-categories', [SelectedController::class, 'investmentCategory']);
    $router->get('search-product-serial', [SelectedController::class, 'searchProductSerial']);
    $router->get('directors', [SelectedController::class, 'director']);
    $router->get('employees', [SelectedController::class, 'employee']);
    $router->get('sellers', [SelectedController::class, 'seller']);
    $router->get('category-directors/{category}', [SelectedController::class, 'categoryDirectors']);
    $router->get('withdrawal/category-directors/{category}', [SelectedController::class, 'withdrawalCategoryDirector']);

    $router->get('ledger-contacts/{contact}', [SelectedController::class, 'ledgerContact']);

    $router->get('cares', [SelectedController::class, 'care']);
    $router->get('stock-transfer-search-serial', [SelectedController::class, 'stockTransferSearchSerial']);
    $router->get('pre-owned-products', [SelectedController::class, 'preOwnedProducts']);
    $router->get('telesale-results', [TelesaleResultController::class, 'index']);
    $router->get('telesale-customers', [SelectedController::class, 'telesaleCustomer']);
    $router->get('order-confirmation-options', [OrderConfirmationOptionController::class, 'index']);
});
