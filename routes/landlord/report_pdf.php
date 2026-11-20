<?php

use App\Http\Controllers\Pos\Report\DepositReportController;
use App\Http\Controllers\Pos\Report\ExpenseReportController;
use App\Http\Controllers\Pos\Report\PurchaseReportController;
use App\Http\Controllers\Pos\Report\PurchaseReturnReportController;
use App\Http\Controllers\Pos\Report\SaleReturnReportController;
use App\Http\Controllers\Pos\Report\SalesReportController;
use App\Http\Controllers\Pos\Report\WithdrawalReportController;
use App\Http\Controllers\Pos\Statement\StatementController;
use App\Http\Controllers\Pos\Statement\StatementPDFController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {

    $router->post('stock-report-pdf', [StatementPDFController::class, 'stockReportPdf'])
        ->name('stock_report_pdf.download');

    $router->post('sale-report-pdf', [SalesReportController::class, 'saleReportPdf'])
        ->name('sale_report_pdf.download');

    $router->post('sale-return-report-pdf', [SaleReturnReportController::class, 'saleReturnReportPdf'])
        ->name('sale_return_report_pdf.download');

    $router->post('purchase-report-pdf', [PurchaseReportController::class, 'purchaseReportPdf'])
        ->name('purchase_report_pdf.download');

    $router->post('purchase-return-report-pdf', [PurchaseReturnReportController::class, 'purchaseReturnReportPdf'])
        ->name('purchase_return_report_pdf.download');

    $router->post('deposit-report-pdf', [DepositReportController::class, 'depositReportPdf'])
        ->name('deposit_report_pdf.download');

    $router->post('withdrawal-report-pdf', [WithdrawalReportController::class, 'withdrawalReportPdf'])
        ->name('withdrawal_report_pdf.download');

    $router->post('transaction-report-pdf', [StatementController::class, 'transactionStatementPdf'])
        ->name('transaction_report_pdf.download');

    $router->post('expense-report-pdf', [ExpenseReportController::class, 'expenseReportPdf'])
        ->name('expense_report_pdf.download');
});

// only for queue worker

Route::group(['prefix' => 'pos'], function (Router $router) {

    $router->get('stock-report-pdf-status/{token}', [StatementPDFController::class, 'stockReportPdfStatus'])
        ->name('stock_report_pdf.status');

    $router->delete('stock-report-pdf-cleanup/{token}', [StatementPDFController::class, 'stockReportPdfCleanup'])
        ->name('stock_report_pdf.cleanup');

    $router->get('expense-report-pdf-status/{token}', [ExpenseReportController::class, 'expenseReportPdfStatus'])
        ->name('expense_report_pdf.status');

    $router->delete('expense-report-pdf-cleanup/{token}', [ExpenseReportController::class, 'expenseReportPdfCleanup'])
        ->name('expense_report_pdf.cleanup');
});
