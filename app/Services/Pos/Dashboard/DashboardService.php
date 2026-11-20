<?php

namespace App\Services\Pos\Dashboard;

use App\Helpers\Traits\DateRangeHelper;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\Sale\SaleItem;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Transaction\Transaction;
use App\Services\BaseService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    use DateRangeHelper;

    public function totalSaleAmount()
    {
        return Sale::query()
            ->sum('grand_total');
    }

    public function totalPurchasesAmount()
    {
        return Purchase::query()
            ->sum('grand_total');
    }

    public function totalSaleReturnAmount()
    {
        return SaleReturn::query()
            ->sum('grand_total');
    }

    public function totalPurchasesReturnAmount()
    {
        return PurchaseReturn::query()
            ->sum('grand_total');
    }

    public function totalCustomer(): int
    {
        return Contact::query()->whereHas('sales')->count();
    }

    public function totalSupplier(): int
    {
        return Contact::query()->whereHas('purchases')->count();
    }

    public function totalPurchasesInvoice(): int
    {
        return Purchase::query()->count();
    }

    public function totalPurchasesReturnInvoice(): int
    {
        return PurchaseReturn::query()->count();
    }

    public function totalSaleInvoice(): int
    {
        return Sale::query()->count();
    }

    public function totalReturnSaleInvoice(): int
    {
        return SaleReturn::query()->count();
    }

    public function manipulatePurchasesOverviewChart($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;
        $purchases = Purchase::query()
            ->select(['id', 'date', 'grand_total'])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $purchases->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('grand_total') : $this->keyValueReceivedAmountSum('purchases', $rows));

        return $dateRange->range
            ? $this->fillPeriods('purchases', $sumGroups, $dateRange)
            : $sumGroups;
    }

    public function manipulatePurchaseReturnOverviewChart($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;
        $purchases = PurchaseReturn::query()
            ->select(['id', 'date', 'grand_total'])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $purchases->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('grand_total') : $this->keyValueReceivedAmountSum('purchases_return', $rows));

        return $dateRange->range
            ? $this->fillPeriods('purchases_return', $sumGroups, $dateRange)
            : $sumGroups;
    }

    public function manipulateSaleOverviewChart($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;
        $sales = Sale::query()
            ->select(['id', 'date', 'grand_total'])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $sales->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('grand_total') : $this->keyValueReceivedAmountSum('sales', $rows));

        return $dateRange->range
            ? $this->fillPeriods('sales', $sumGroups, $dateRange)
            : $sumGroups;
    }

    public function manipulateSaleReturnOverviewChart($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;
        $sales = SaleReturn::query()
            ->select(['id', 'date', 'grand_total'])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $sales->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('grand_total') : $this->keyValueReceivedAmountSum('sale_return', $rows));

        return $dateRange->range
            ? $this->fillPeriods('sale_return', $sumGroups, $dateRange)
            : $sumGroups;
    }

    public function topSaleProduct(): \Illuminate\Database\Eloquent\Collection
    {
        $currentYear = Carbon::now()->year;

        return SaleItem::query()
            ->select('products.product_name', DB::raw('CAST(SUM(sale_items.quantity) AS UNSIGNED) as quantity'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereYear('sales.date', $currentYear)
            ->groupBy('products.product_name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();
    }

    public function topSaleCustomer(): \Illuminate\Database\Eloquent\Collection
    {
        $currentYearMonth = Carbon::now()->month;

        return Sale::query()
            ->select('customers.name', DB::raw('COUNT(sales.contact_id) as total_sale'))
            ->join('customers', 'sales.contact_id', '=', 'customers.id')
            ->whereMonth('sales.date', $currentYearMonth)
            ->whereYear('sales.date', date('Y'))
            ->groupBy('sales.contact_id', 'customers.name')
            ->orderByDesc('total_sale')
            ->limit(5)
            ->get();
    }

    public function receivedPayment($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        $transactions = Transaction::query()
            ->whereIn('model_type', [Sale::class, PurchaseReturn::class])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $transactions->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('amount') : [
            'received_payment' => $rows->sum('amount'),
        ]);

        return $dateRange->range
            ? $this->fillPeriods('received_payment', $sumGroups, $dateRange)
            : $sumGroups;
    }

    public function sentPayment($rangeType): \Illuminate\Database\Eloquent\Collection|array|Collection
    {
        $dateRange = $this->dateRange($rangeType);
        $range = $dateRange->range;

        $transactions = Transaction::query()
            ->whereIn('model_type', [Purchase::class, SaleReturn::class])
            ->when($range, function ($query) use ($range) {
                $query->whereBetween('date', $range);
            })->get();

        $sumGroups = $transactions->groupBy(fn ($row) => Carbon::parse($row->date)->format($dateRange->context))->map(fn ($rows) => $dateRange->range ? $rows->sum('amount') : [
            'sent_payment' => $rows->sum('amount'),
        ]);

        return $dateRange->range
            ? $this->fillPeriods('sent_payment', $sumGroups, $dateRange)
            : $sumGroups;
    }

    private function keyValueReceivedAmountSum($key, $row): array
    {
        return [$key => $row->sum('grand_total')];
    }

    private function fillPeriods($keys, $sumGroups, $dateRange): array
    {
        $period = CarbonPeriod::create(...$dateRange->range);

        $data = [];

        foreach ($period->toArray() as $date) {

            $format = $date->format($dateRange->context);

            $data[$format] = [
                $keys => $sumGroups[$format] ?? 0,
                'context' => $format,
                'date' => $date->format('Y-m-d'),
            ];
        }

        return $data;
    }
}
