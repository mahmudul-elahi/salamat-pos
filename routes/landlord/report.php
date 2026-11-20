<?php

use App\Http\Controllers\CustomerReportDownloadController;
use App\Http\Controllers\Pos\Report\CustomerReportController;
use App\Http\Controllers\Pos\Report\ExpenseReportController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\Report\StockReportController;
use App\Http\Controllers\SupplierReportDownloadController;
use App\Http\Controllers\Pos\ProfitLoss\ProfitLossController;
use App\Http\Controllers\Pos\Report\SupplierReportController;
use App\Http\Controllers\Pos\Report\DepositReportController;
use App\Http\Controllers\Pos\Report\BrandSaleReportController;
use App\Http\Controllers\Pos\Report\CategorySaleReportController;
use App\Http\Controllers\Pos\Report\SalesReportController;
use App\Http\Controllers\Pos\Report\SaleReturnReportController;
use App\Http\Controllers\Pos\Report\PurchaseReportController;
use App\Http\Controllers\Pos\Report\PurchaseReturnReportController;
use App\Http\Controllers\Pos\Report\WithdrawalReportController;
use App\Http\Controllers\Pos\Report\StockTransferReportController;
use App\Http\Controllers\Pos\Report\CashTransferReportController;
use App\Http\Controllers\Pos\Report\DamageProductReportController;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->get('stock-reports', [StockReportController::class, 'index'])
        ->name('stock_reports.view');

    $router->get('expense-reports', [ExpenseReportController::class, 'index'])
        ->name('expense_reports.view');

    $router->get('supplier-reports', [SupplierReportController::class, 'index'])
        ->name('supplier_reports.view');

    $router->get('supplier-reports/{id}', [SupplierReportController::class, 'supplierDetails'])
        ->name('supplier_reports.details');

    $router->get('supplier-ledger-reports-download/{id}', [SupplierReportDownloadController::class, 'download'])
        ->name('supplier_reports.details');

    $router->get('supplier-purchase-reports/{id}', [SupplierReportController::class, 'supplierPurchases'])
        ->name('supplier_purchase_reports.view');

    $router->get('supplier-purchase-return-reports/{id}', [SupplierReportController::class, 'supplierPurchaseReturns'])
        ->name('supplier_purchase_return_reports.view');

    $router->get('supplier-purchase-transaction-reports/{id}', [SupplierReportController::class, 'purchaseTransactionBySupplier'])
        ->name('supplier_purchase_transaction_reports.view');

    $router->get('supplier-purchase-return-transaction-reports/{id}', [SupplierReportController::class, 'purchaseReturnTransactionBySupplier'])
        ->name('supplier_purchase_return_transaction_reports.view');

    $router->get('supplier-ledger-reports/{id}', [SupplierReportController::class, 'supplierLedgerDetail'])
        ->name('supplier_ledger_reports.view');

    $router->get('profit-loss-statistic/{range_type?}', [ProfitLossController::class, 'statistic'])->name('profit_loss_statistics.view');

    $router->get('brand-sale-reports', [BrandSaleReportController::class, 'index'])
        ->name('brand_sale_reports.view');

    $router->get('category-sale-reports', [CategorySaleReportController::class, 'index'])
        ->name('category_sale_reports.view');

    $router->get('sale-by-product-reports', [StockReportController::class, 'saleByProduct'])
        ->name('sale_by_product_reports.view');

    $router->get('sale-reports', [SalesReportController::class, 'index'])
        ->name('sale_reports.view');

    $router->get('sale-return-reports', [SaleReturnReportController::class, 'index'])
        ->name('sale_return_reports.view');

    $router->get('purchase-reports', [PurchaseReportController::class, 'index'])
        ->name('purchase_reports.view');

    $router->get('purchase-return-reports', [PurchaseReturnReportController::class, 'index'])
        ->name('purchase_return_reports.view');

    $router->post('purchase-report-pdf', [PurchaseReportController::class, 'purchaseReportPdf'])
        ->name('purchase_reports.pdf');

    $router->get('stock-transfer-reports', [StockTransferReportController::class, 'index'])
        ->name('stock_transfer_reports.view');

    $router->post('stock-transfer-report-pdf', [StockTransferReportController::class, 'pdf'])
        ->name('stock_transfer_reports.pdf');

    $router->get('cash-transfer-reports', [CashTransferReportController::class, 'index'])
        ->name('cash_transfer_reports.view');

    $router->post('cash-transfer-report-pdf', [CashTransferReportController::class, 'pdf'])
        ->name('cash_transfer_reports.pdf');

    $router->get('damage-product-reports', [DamageProductReportController::class, 'index'])
        ->name('damage_product_reports.view');

    $router->post('damage-product-report-pdf', [DamageProductReportController::class, 'pdf'])
        ->name('damage_product_reports.pdf');

    $router->get('ledgers', [CustomerReportController::class, 'index'])
        ->name('ledgers.view');

    $router->get('customer-ledger-reports/{contactId}', [CustomerReportController::class, 'ledgerReport'])
        ->name('customer_ledger_reports.view');

    $router->get('customer-ledger-reports-download/{contactId}', [CustomerReportDownloadController::class, 'download'])
        ->name('customer_ledger_reports.download');

    $router->delete('ledger-reports/{ledgerId}', [CustomerReportController::class, 'delete'])
        ->name('ledger_reports.delete');



    $router->get('deposit-reports', [DepositReportController::class, 'index'])
        ->name('deposit_reports.view');

    $router->get('withdrawal-reports', [WithdrawalReportController::class, 'index'])
        ->name('withdrawal_reports.view');

    $router->get('profit-loss-reports', [ProfitLossController::class, 'index'])
        ->name('profit_loss_reports.index');

    $router->post('profit-loss-pdf', [ProfitLossController::class, 'generatePdf'])
        ->name('profit_loss_pdf.generate');
});

// Only query worker can access
Route::group(['prefix' => 'pos'], function (Router $router) {

    $router->get('profit-loss-pdf-status/{token}', [ProfitLossController::class, 'pdfStatus'])
        ->name('profit_loss_pdf.status');

    $router->delete('profit-loss-pdf-cleanup/{token}', [ProfitLossController::class, 'pdfCleanup'])
        ->name('profit_loss_pdf.cleanup');
});
