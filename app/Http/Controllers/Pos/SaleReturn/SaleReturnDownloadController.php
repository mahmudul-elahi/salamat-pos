<?php

namespace App\Http\Controllers\Pos\SaleReturn;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Pos\SaleReturn\SaleReturn;
use Illuminate\Http\JsonResponse;
use Milon\Barcode\DNS1D;

class SaleReturnDownloadController extends Controller
{
    public function invoiceData(SaleReturn $saleReturn): JsonResponse
    {
        $saleReturn->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues',
            'payments.paymentMethod',
        ]);

        $customer = $saleReturn->contact;
        $address  = '';
        if ($customer) {
            if ($customer->shipping_address_id && $customer->shippingAddress) {
                $address = $customer->shippingAddress->getFullAddressAttribute() ?? '';
            } else {
                $address = $customer->getFullAddress() ?? '';
            }
        }

        //$receivedAmount = $saleReturn->getTransactionAmountAttribute();
        //$dueAmount      = max(0, $saleReturn->grand_total - $receivedAmount);
        $totalQuantity  = $saleReturn->items->sum('quantity');
        $totalUnitPrice = $saleReturn->items->sum(fn($i) => $i->unit_price * $i->quantity);
        $totalDiscount  = $saleReturn->items->sum(fn($i) => (float)($i->discount_amount ?? 0));

        $barcodeBase64 = (new DNS1D)->getBarcodePNG($saleReturn->full_invoice_number, 'C128', 1, 40);

        $groupedItems = $saleReturn->items->groupBy('product_variant_id');

        $itemRows = $groupedItems->map(function ($items) {
            $first = $items->first();
            return [
                'product_name'  => $first->product?->product_name,
                'product_image' => to_cdn_url($first->product?->product_image),
                'attributes'    => $first->productVariant?->attributeValues?->pluck('value') ?? [],
                'total_qty'     => $items->sum('quantity'),
                'lines'         => $items->map(fn($item) => [
                    'serial_number'   => $item->serial_number,
                    'unit_price'      => $item->unit_price,
                    'discount_amount' => (float)($item->discount_amount ?? 0),
                    'line_total'      => ($item->unit_price * $item->quantity) - ($item->discount_amount ?? 0),
                ])->values(),
            ];
        })->values();

        $paymentSummary = $saleReturn->payments
            ->groupBy('payment_method_id')
            ->map(fn($txns) => [
                'method' => optional($txns->first()->paymentMethod)->name,
                'amount' => $txns->sum('amount'),
            ])->values();

        return response()->json([
            'customer' => [
                'name'         => $customer?->name,
                'company_name' => $customer?->company_name,
                'phone'        => $customer?->phone,
                'email'        => $customer?->email,
                'address'      => $address,
            ],
            'sale_return' => [
                'date'                => $saleReturn->date,
                'full_invoice_number' => $saleReturn->full_invoice_number,
                'grand_total'         => $saleReturn->grand_total,
                'status'              => $saleReturn->status,
                'order_tax'           => $saleReturn->order_tax,
                'shipping_cost'       => $saleReturn->shipping_cost,
                'note'                => $saleReturn->note,
            ],
            'items'            => $itemRows,
            'items_count'      => $groupedItems->count(),
            'barcode'          => 'data:image/png;base64,' . $barcodeBase64,
            'payment_summary'  => $paymentSummary,
            'total_quantity'   => $totalQuantity,
            'total_unit_price' => $totalUnitPrice,
            'total_discount'   => $totalDiscount,
            //'received_amount'  => $receivedAmount,
            //'due_amount'       => $dueAmount,
        ]);
    }

    public function download(SaleReturn $saleReturn)
    {
        $pdf = $this->generatePdf($saleReturn);

        if (request()->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $saleReturn->full_invoice_number . '.pdf"',
            ]);
        }

        return $pdf->download("{$saleReturn->full_invoice_number}.pdf");
    }

    public function generatePdf(SaleReturn $saleReturn)
    {
        $saleReturn->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues.attribute',
            'payments',
            'tenant'
        ]);
        $logo = to_cdn_url(config('settings.application.company_logo'));

        $returnAmount = $saleReturn->getTransactionAmountAttribute();
        $dueAmount = $saleReturn->grand_total - $returnAmount;

        $data = [
            'saleReturn' => $saleReturn,
            'customer'   => $saleReturn->contact,
            'items'      => $saleReturn->items,
            'payments'   => $saleReturn->payments,
            'tenant'     => $saleReturn->tenant,
            'return_amount'=> $returnAmount,
            'due_amount'   => $dueAmount,
            'logo'             => $logo,
        ];

        return Pdf::loadView('pdf.sale-return-invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }
}
