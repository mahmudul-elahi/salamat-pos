<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Contact\ContactHistoryController;
use App\Http\Controllers\Pos\Customer\CustomerController;
use App\Http\Controllers\Pos\Customer\CustomerPaymentController;
use App\Http\Controllers\Pos\Director\DirectorController;
use App\Http\Controllers\Pos\Payment\PaymentHistoryController;
use App\Http\Controllers\Pos\People\ContactController;
use App\Http\Controllers\Pos\People\OnlineContactController;
use App\Http\Controllers\Pos\People\PeopleController;
use App\Http\Controllers\Pos\Supplier\SupplierController;
use App\Http\Controllers\Tenant\AdvanceCreditController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->apiResource('suppliers', SupplierController::class);
    $router->apiResource('customers', CustomerController::class);
    $router->apiResource('contacts', ContactController::class);

    $router->patch('customers/{customer}/rating', [CustomerController::class, 'rating'])
        ->name('customer_ratings.update');

    $router->patch('suppliers/{supplier}/rating', [SupplierController::class, 'rating'])
        ->name('supplier_ratings.update');

    $router->patch('customers/{customer}/note', [CustomerController::class, 'note'])
        ->name('customer_notes.update');

    $router->patch('suppliers/{supplier}/note', [SupplierController::class, 'note'])
        ->name('supplier_notes.update');

    // POST, not PATCH — PHP does not parse multipart bodies on PATCH requests.
    $router->post('suppliers/{supplier}/price-document', [SupplierController::class, 'priceDocument'])
        ->name('supplier_price_documents.update');

    $router->post('customers/{customer}/price-document', [CustomerController::class, 'priceDocument'])
        ->name('customer_price_documents.update');

    $router->post('online-contacts/{onlineContact}/price-document', [OnlineContactController::class, 'priceDocument'])
        ->name('online_contact_price_documents.update');

    // Chunked variants — one slice per request, so a large price list never
    // has to fit inside a single upload. Named ".edit" so they resolve to the
    // same update_* permissions as the plain uploads above.
    $router->post('suppliers/{supplier}/price-document-chunk', [SupplierController::class, 'priceDocumentChunk'])
        ->name('supplier_price_documents.edit');

    $router->post('customers/{customer}/price-document-chunk', [CustomerController::class, 'priceDocumentChunk'])
        ->name('customer_price_documents.edit');

    $router->post('online-contacts/{onlineContact}/price-document-chunk', [OnlineContactController::class, 'priceDocumentChunk'])
        ->name('online_contact_price_documents.edit');

    $router->apiResource('online-contacts', OnlineContactController::class);

    $router->get('online-contacts/{onlineContact}/orders', [OnlineContactController::class, 'orders'])
        ->name('customer_orders.index');

    $router->get('online-contacts/{onlineContact}/elite-histories', [OnlineContactController::class, 'eliteHistories'])
        ->name('customer_elite_histories.index');

    $router->get('online-contacts/{onlineContact}/addresses', [OnlineContactController::class, 'addresses'])
        ->name('customer_addresses.index');

    $router->get('online-contacts/{onlineContact}/reviews', [OnlineContactController::class, 'reviews'])
        ->name('customer_reviews.index');

    $router->get('online-contacts/{onlineContact}/cart', [OnlineContactController::class, 'cart'])
        ->name('customer_shopping_carts.index');

    $router->get('online-contacts/{onlineContact}/wishlist', [OnlineContactController::class, 'wishlist'])
        ->name('customer_wishlists.index');

    $router->get('online-contacts/{onlineContact}/sell-device-requests', [OnlineContactController::class, 'sellDeviceRequests'])
        ->name('customer_sell_device_requests.index');

    $router->get('payments', [CustomerPaymentController::class, 'index'])
        ->name('payments.view');

    $router->get('payment-history', [PaymentHistoryController::class, 'index'])
        ->name('payment_history.view');

    $router->delete('payment-history/{ledgerId}', [PaymentHistoryController::class, 'delete'])
        ->name('payment_history.delete');

    $router->get('debit-vouchers/{ledger}', [PaymentHistoryController::class, 'debitVoucher'])
        ->name('debit_voucher.view');

    $router->get('credit-vouchers/{ledger}', [PaymentHistoryController::class, 'creditVoucher'])
        ->name('credit_voucher.view');

    // $router->get('customers-due/{customer}', [App\Http\Controllers\Pos\Customer\CustomerPaymentController::class, 'getDue'])->name('customers.due');

    $router->post('customers-payment', [CustomerPaymentController::class, 'store'])
        ->name('customer_payment.receive');

    $router->post('supplier-payment-send', [CustomerPaymentController::class, 'supplierPaymentSend'])
        ->name('supplier_payment.send');

    $router->post('advance-credit/store', [AdvanceCreditController::class, 'store'])
        ->name('advance_credit.store');

    $router->apiResource('directors', DirectorController::class);

    $router->get('directors/{director}/statistics', [DirectorController::class, 'statistics'])
        ->name('directors_statistics.view');

    $router->get('directors/{director}/deposits', [DirectorController::class, 'deposit'])
        ->name('directors_deposits.view');

    $router->get('directors/{director}/withdrawals', [DirectorController::class, 'withdrawals'])
        ->name('directors_withdrawals.view');

    $router->get('directors/{director}/shop-balances', [DirectorController::class, 'shopBalances'])
        ->name('directors_shop_balances.view');

    $router->post('directors/{director}/profile-pdf', [DirectorController::class, 'generatePdf'])
        ->name('director_profile_pdf.generate');

    $router->post('directors/{director}/shop-balances/{tenant}/profile-pdf', [DirectorController::class, 'generateShopPdf'])
        ->name('director_shop_profile_pdf.generate');
});

// Only query worker can access - the UUID token is the de facto
// authorization for these two endpoints, matching the report PDF pattern
// (see routes/landlord/report.php).
Route::group(['prefix' => 'landlord'], function (Router $router) {

    $router->get('director-profile-pdf-status/{token}', [DirectorController::class, 'pdfStatus'])
        ->name('director_profile_pdf.status');

    $router->delete('director-profile-pdf-cleanup/{token}', [DirectorController::class, 'pdfCleanup'])
        ->name('director_profile_pdf.cleanup');
});

// Route name "people.index" derives the single view_people permission via
// 'auth.permission' (the standard resource.action convention). Any user
// granted view_people can see the whole merged list regardless of their
// individual view_customers/view_suppliers/view_online_contacts access.
Route::group(['prefix' => 'landlord', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('people', [PeopleController::class, 'index'])
        ->name('people.index');

    // The route-level permission below only gates access to the bulk-delete
    // action itself - which of the selected rows actually get deleted is
    // still checked per-row inside PeopleController::bulkDelete() against
    // that row's own delete_customers/delete_suppliers/delete_online_contacts
    // permission, since a mixed offline/supplier/online selection can't be
    // authorized by a single permission check.
    $router->post('people/bulk-delete', [PeopleController::class, 'bulkDelete'])
        ->name('people.bulk_delete');
});

// Route name "timeline_people_history.view" derives view_timeline_people_history
// via 'auth.permission'. Note ContactHistoryController::timeline() ALSO checks
// each of the seven underlying view_* permissions itself (view_sales,
// view_purchases, ...) per data source — so a user needs view_timeline_people_history
// just to reach the endpoint, and then only sees the sections their
// individual view_* permissions allow.
Route::group(['prefix' => 'pos/contact-history', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('{contact}/timeline', [ContactHistoryController::class, 'timeline'])
        ->name('timeline_people_history.view');
});
