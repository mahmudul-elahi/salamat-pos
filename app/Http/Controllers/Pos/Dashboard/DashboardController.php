<?php

namespace App\Http\Controllers\Pos\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Pos\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $service) {}


    public function statistic(): array
    {
        $totalSaleAmount = $this->service->totalSaleAmount();
        $totalPurchasesAmount = $this->service->totalPurchasesAmount();
        $totalSaleReturnAmount = $this->service->totalSaleReturnAmount();
        $totalPurchasesReturnAmount = $this->service->totalPurchasesReturnAmount();

        return [
            'total_sale_amount' => $totalSaleAmount,
            'total_purchases_amount' => $totalPurchasesAmount,
            'total_sale_return_amount' => $totalSaleReturnAmount,
            'total_purchases_return_amount' => $totalPurchasesReturnAmount,
            'total_customer' => $this->service->totalCustomer(),
            'total_supplier' => $this->service->totalSupplier(),
            'total_purchases_invoice' => $this->service->totalPurchasesInvoice(),
            'total_purchases_return_invoice' => $this->service->totalPurchasesReturnInvoice(),
            'total_sale_invoice' => $this->service->totalSaleInvoice(),
            'total_return_sale_invoice' => $this->service->totalReturnSaleInvoice(),
        ];
    }

    public function purchasesAndSales($rangeType = 4)
    {
        $purchase = $this->service->manipulatePurchasesOverviewChart($rangeType);
        $sale = $this->service->manipulateSaleOverviewChart($rangeType);

        return ['purchases' => $purchase, 'sales' => $sale];
    }

    public function topSellProduct()
    {
        return $this->service->topSaleProduct();
    }

    public function topSellCustomer()
    {
        return $this->service->topSaleCustomer();
    }

    public function paymentSentAndReceived($rangeType = 4)
    {
        $totalReceivedAmount = $this->service->receivedPayment($rangeType);
        $totalSentAmount = $this->service->sentPayment($rangeType);

        return [
            'total_received_amount' => $totalReceivedAmount,
            'total_sent_amount' => $totalSentAmount,
        ];
    }

    public function purchaseSaleAndReturnOverview($rangeType = 4)
    {
        $purchase = $this->service->manipulatePurchasesOverviewChart($rangeType);
        $purchaseReturn = $this->service->manipulatePurchaseReturnOverviewChart($rangeType);
        $sale = $this->service->manipulateSaleOverviewChart($rangeType);
        $saleReturn = $this->service->manipulateSaleReturnOverviewChart($rangeType);

        return [
            'purchases' => $purchase,
            'purchase_returns' => $purchaseReturn,
            'sales' => $sale,
            'sale_returns' => $saleReturn
        ];
    }
}
