<?php

use App\Http\Controllers\Pos\Statement\DailyClosingController;
use App\Http\Controllers\Pos\ProfitLoss\ProfitLossController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\Statement\StatementController;
use App\Http\Controllers\Pos\Statement\StatementPDFController;
use App\Http\Controllers\Pos\Statement\PurchaseStatementController;
use App\Http\Controllers\Pos\Statement\SaleStatementController;
use App\Http\Controllers\Pos\Statement\SaleReturnStatementController;
use App\Http\Controllers\Pos\Statement\PurchaseReturnStatementController;
use App\Http\Controllers\Pos\Statement\DailyPayReceivableController;
use App\Http\Controllers\Pos\Statement\ExpenseStatementController;
use App\Http\Controllers\Pos\Statement\WithdrawalStatementController;
use App\Http\Controllers\Pos\Statement\DepositStatementController;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {


    $router->get('daily-closings', [DailyClosingController::class, 'index'])
        ->name('daily_closings.view');

    $router->get('daily-closing-balance', [DailyClosingController::class, 'todayClosingBalance'])
        ->name('daily_closing_balance.view');

    $router->get('daily-closing-balance-download', [DailyClosingController::class, 'todayClosingBalanceDownload'])
        ->name('daily_closing_balance.download');

    $router->get('daily-closing-pdf-download', [DailyClosingController::class, 'downloadPdf'])
        ->name('daily_closings.download');

    $router->get('daily-closing-profit-loss', [ProfitLossController::class, 'todayIndex'])
        ->name('daily_closing_profit_loss.view');

    $router->post('daily-closing-profit-loss-pdf', [ProfitLossController::class, 'generateTodayPdf'])
        ->name('daily_closing_profit_loss_pdf.generate');

    $router->get('closing-statements', [StatementController::class, 'index'])
        ->name('stock_statements.view');

    $router->post('stock-statement-pdf', [StatementPDFController::class, 'stockStatementPdf'])
        ->name('stock_statement_pdf.generate');



    $router->post('sale-statement-pdf', [SaleStatementController::class, 'saleStatementPdf'])
        ->name('sale_statement_pdf.generate');

    $router->post('sale-return-statement-pdf', [SaleReturnStatementController::class, 'saleReturnStatementPdf'])
        ->name('sale_return_statement_pdf.generate');

    $router->post('purchase-statement-pdf', [PurchaseStatementController::class, 'purchaseStatementPdf'])
        ->name('purchase_statement_pdf.generate');

    $router->post('purchase-return-statement-pdf', [PurchaseReturnStatementController::class, 'purchaseReturnStatementPdf'])
        ->name('purchase_return_statement_pdf.generate');

    $router->post('expense-statement-pdf', [ExpenseStatementController::class, 'expenseStatementPdf'])
        ->name('expense_statement_pdf.generate');


    $router->post('withdrawal-statement-pdf', [WithdrawalStatementController::class, 'withdrawalStatementPdf'])
        ->name('withdrawal_statement_pdf.generate');

    $router->post('deposit-statement-pdf', [DepositStatementController::class, 'depositStatementPdf'])
        ->name('deposit_statement_pdf.generate');

    $router->get('sale-statements', [SaleStatementController::class, 'index'])
        ->name('sale_statements.view');

    $router->get('purchase-statements', [PurchaseStatementController::class, 'index'])
        ->name('purchase_statements.view');

    $router->get('purchase-return-statements', [PurchaseReturnStatementController::class, 'index'])
        ->name('purchase_return_statements.view');

    $router->get('sale-return-statements', [SaleReturnStatementController::class, 'index'])
        ->name('sale_return_statements.view');

    $router->get('transactions', [StatementController::class, 'transaction'])
        ->name('transaction_reports.view');


    $router->get('expense-statements', [ExpenseStatementController::class, 'index'])
        ->name('expense_statements.view');

    $router->get('withdrawal-statements', [WithdrawalStatementController::class, 'index'])
        ->name('withdrawal_statements.view');

    $router->get('deposit-statements', [DepositStatementController::class, 'index'])
        ->name('deposit_statements.view');

    $router->get('payables', [DailyPayReceivableController::class, 'payAble'])
        ->name('daily_payables.view');

    $router->get('receivables', [DailyPayReceivableController::class, 'receivedAble'])
        ->name('daily_receivables.view');

    $router->get('balance-sheets', [DailyPayReceivableController::class, 'contactBalanceSheet'])
        ->name('balance_sheets.view');
});


// Only query worker can access

Route::group(['prefix' => 'pos'], function (Router $router) {

    $router->get('stock-statement-pdf-status/{token}', [StatementPDFController::class, 'stockStatementPdfStatus'])
        ->name('stock_statement_pdf.status');

    $router->delete('stock-statement-pdf-cleanup/{token}', [StatementPDFController::class, 'stockStatementPdfCleanup'])
        ->name('stock_statement_pdf.cleanup');
});
