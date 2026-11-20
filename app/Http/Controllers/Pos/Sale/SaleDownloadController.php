<?php

namespace App\Http\Controllers\Pos\Sale;

use App\Http\Controllers\Controller;
use App\Models\Pos\Sale\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Milon\Barcode\DNS1D;

class SaleDownloadController extends Controller
{

    public function download(Sale $sale)
    {
        $pdf = $this->generatePdf($sale);

        if (request()->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $sale->full_invoice_number . '.pdf"',
            ]);
        }

        return $pdf->download("{$sale->full_invoice_number}.pdf");
    }

    protected function generatePdf(Sale $sale)
    {
        $sale->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues.attribute',
            'payments.paymentMethod',
            'tenant',
        ]);

        $receivedAmount = $sale->getTransactionAmountAttribute();
        $dueAmount      = max(0, $sale->grand_total - $receivedAmount);
        $totalQuantity  = $sale->items->sum('quantity');

        $totalUnitPrice = $sale->items->sum(function ($item) {
            $amount = $item->unit_price * $item->quantity;
            return $item->sale_type === 'exchange' ? -$amount : $amount;
        });

        $totalDiscount = $sale->items->sum(function ($item) {
            if ($item->sale_type === 'exchange') return 0;
            return (float)($item->discount_amount ?? 0);
        });

        $barcodePNG = (new DNS1D)->getBarcodePNG($sale->full_invoice_number, 'C128', 1, 40);

        $data = [
            'taka'                   => public_path('assets/images/taka.png'),
            'totalQuantity'          => $totalQuantity,
            'totalUnitPrice'         => $totalUnitPrice,
            'totalDiscount'          => $totalDiscount,
            'sale'                   => $sale,
            'customer'               => $sale->contact,
            'items'                  => $sale->items,
            'payments'               => $sale->payments,
            'tenant'                 => $sale->tenant,
            'barcode'                => 'data:image/png;base64,' . $barcodePNG,
            'received_amount'        => $receivedAmount,
            'due_amount'             => $dueAmount,
            'additional_charge_name' => $sale->additional_charge_name,
            'additional_charge'      => $sale->additional_charge,
            'order_tax'              => $sale->order_tax,
            'shipping_cost'          => $sale->shipping_cost,
            'payment_summary'        => $sale->paymentSummary(),
        ];

        return Pdf::loadView('pdf.sale-invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }

    public function invoiceData(Sale $sale): JsonResponse
    {
        $sale->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues',
            'payments.paymentMethod',
        ]);

        $customer = $sale->contact;
        $address  = $sale->address ?? $customer->getFullAddress() ?? '';

        // $sale->grand_total is only the item total after line-level discounts -
        // shipping is added and any coupon is subtracted separately (see
        // SaleService::create() / OrderController::approveOrderWithSerials(),
        // which both save shipping_cost/coupon_amount as their own columns
        // rather than folding them into grand_total). The final amount the
        // customer actually owes needs all three combined.
        $finalTotal     = $sale->grand_total + $sale->shipping_cost - $sale->coupon_amount;
        $receivedAmount = $sale->getTransactionAmountAttribute();
        $dueAmount      = max(0, $finalTotal - $receivedAmount);
        $totalQuantity  = $sale->items->sum('quantity');

        $totalUnitPrice = $sale->items->sum(function ($item) {
            $amount = $item->unit_price * $item->quantity;
            return $item->sale_type === 'exchange' ? -$amount : $amount;
        });

        $totalDiscount = $sale->items->sum(function ($item) {
            if ($item->sale_type === 'exchange') return 0;
            return (float)($item->discount_amount ?? 0);
        });

        $barcodeBase64 = (new DNS1D)->getBarcodePNG($sale->full_invoice_number, 'C128', 1, 40);

        $groupedItems = $sale->items->groupBy('product_variant_id');

        $itemRows = $groupedItems->map(function ($items) {
            $first = $items->first();
            return [
                'product_name'  => $first->product?->product_name,
                'product_image' => to_cdn_url($first->product?->product_image),
                'warranty_day'  => $first->product?->warranty_day,
                'attributes'    => $first->productVariant?->attributeValues?->pluck('value') ?? [],
                'serials'       => $items->pluck('serial_number')->filter()->values(),
                'total_qty'     => $items->sum('quantity'),
                'lines'         => $items->map(function ($item) {
                    $lineTotal  = ($item->unit_price * $item->quantity) - ($item->discount_amount ?? 0);
                    $isExchange = $item->sale_type === 'exchange';
                    return [
                        'unit_price'      => $item->unit_price,
                        'discount_amount' => $isExchange ? 0 : ($item->discount_amount ?? 0),
                        'line_total'      => $isExchange ? -$lineTotal : $lineTotal,
                        'is_exchange'     => $isExchange,
                        'serial_number'   => $item->serial_number,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'customer' => [
                'name'         => $customer?->name,
                'company_name' => $customer?->company_name,
                'phone'        => $customer?->phone,
                'email'        => $customer?->email,
                'address'      => $address,
            ],
            'sale' => [
                'date'                   => $sale->date,
                'full_invoice_number'    => $sale->full_invoice_number,
                'grand_total'            => $finalTotal,
                'status'                 => $sale->status,
                'additional_charge'      => $sale->additional_charge,
                'additional_charge_name' => $sale->additional_charge_name,
                'order_tax'              => $sale->order_tax,
                'shipping_cost'          => $sale->shipping_cost,
            ],
            'items'            => $itemRows,
            'items_count'      => $groupedItems->count(),
            'barcode'          => 'data:image/png;base64,' . $barcodeBase64,
            'payment_summary'  => $sale->paymentSummary()->values(),
            'total_quantity'   => $totalQuantity,
            'total_unit_price' => $totalUnitPrice,
            'coupon_amount'     => $sale->coupon_amount,
            'total_discount'   => $totalDiscount + $sale->coupon_amount,
            'received_amount'  => $receivedAmount,
            'due_amount'       => $dueAmount,
        ]);
    }
}
