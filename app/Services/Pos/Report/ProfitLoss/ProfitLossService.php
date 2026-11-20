<?php

namespace App\Services\Pos\Report\ProfitLoss;

use App\Helpers\Traits\DateRangeHelper;
use App\Models\Pos\Expense\Expense;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Transaction\Transaction;
use App\Services\BaseService;

class ProfitLossService extends BaseService
{
    use DateRangeHelper;

    // ---- Amounts ----
    public function totalSaleAmount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return Sale::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('grand_total');
    }

    public function totalPurchasesAmount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return Purchase::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('grand_total');
    }

    public function totalSaleReturnAmount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return SaleReturn::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('grand_total');
    }

    public function totalPurchasesReturnAmount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return PurchaseReturn::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('grand_total');
    }

    // ---- Counts ----
    public function totalSaleCount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return Sale::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->count();
    }

    public function totalPurchasesCount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return Purchase::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->count();
    }

    public function totalSaleReturnCount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return SaleReturn::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->count();
    }

    public function totalPurchasesReturnCount($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return PurchaseReturn::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->count();
    }

    // ---- Payment Summaries ----
    public function totalPaymentReceived($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        return Transaction::query()
            ->whereIn('model_type', [Sale::class, PurchaseReturn::class])
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('amount');
    }

    public function totalPaymentSent($rangeType = null)
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        $purchaseAndReturnPayments = Transaction::query()
            ->whereIn('model_type', [Purchase::class, SaleReturn::class])
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('amount');

        $expensePayments = Expense::query()
            ->when($range, fn ($q) => $q->whereBetween('date', $range))
            ->sum('amount');

        return $purchaseAndReturnPayments + $expensePayments;
    }

    // ---- Profit / Loss ----
    public function calculateProfitLoss($rangeType = null)
    {
        $totalSales = $this->totalSaleAmount($rangeType);
        $totalPurchaseReturns = $this->totalPurchasesReturnAmount($rangeType);
        $totalPurchases = $this->totalPurchasesAmount($rangeType);
        $totalSaleReturns = $this->totalSaleReturnAmount($rangeType);
        $totalExpenses = Expense::query()
            ->when($this->dateRange($rangeType)->range, fn ($q) => $q->whereBetween('date', $this->dateRange($rangeType)->range))
            ->sum('amount');

        $profitOrLoss = ($totalSales + $totalPurchaseReturns) - ($totalPurchases + $totalSaleReturns + $totalExpenses);

        return [
            'total_sales' => $totalSales,
            'total_purchase_returns' => $totalPurchaseReturns,
            'total_purchases' => $totalPurchases,
            'total_sale_returns' => $totalSaleReturns,
            'total_expenses' => $totalExpenses,
            'net_profit_loss' => $profitOrLoss,
            'status' => $profitOrLoss >= 0 ? 'profit' : 'loss',
        ];
    }
}
