<?php

namespace App\Http\Controllers\Pos\PurchaseReturn;

use App\Http\Controllers\Controller;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Milon\Barcode\DNS1D;

class PurchaseReturnDownloadController extends Controller
{
    public function invoiceData(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $purchaseReturn->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues',
            'payments.paymentMethod',
        ]);

        $supplier       = $purchaseReturn->contact;
        $receivedAmount = $purchaseReturn->getTransactionAmountAttribute();
        $dueAmount      = max(0, $purchaseReturn->grand_total - $receivedAmount);
        $totalQuantity  = $purchaseReturn->items->sum('quantity');
        $totalUnitPrice = $purchaseReturn->items->sum(fn($i) => $i->unit_price * $i->quantity);
        $totalDiscount  = $purchaseReturn->items->sum(fn($i) => (float)($i->discount_amount ?? 0));

        $barcodeBase64 = (new DNS1D)->getBarcodePNG($purchaseReturn->full_invoice_number, 'C128', 1, 40);

        $groupedItems = $purchaseReturn->items->groupBy('product_variant_id');

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

        $paymentSummary = $purchaseReturn->payments
            ->groupBy('payment_method_id')
            ->map(fn($txns) => [
                'method' => optional($txns->first()->paymentMethod)->name,
                'amount' => $txns->sum('amount'),
            ])->values();

        return response()->json([
            'supplier' => [
                'name'    => $supplier?->name,
                'phone'   => $supplier?->phone,
                'email'   => $supplier?->email,
                'address' => $supplier?->address,
            ],
            'purchase_return' => [
                'date'                => $purchaseReturn->date,
                'full_invoice_number' => $purchaseReturn->full_invoice_number,
                'grand_total'         => $purchaseReturn->grand_total,
                'status'              => $purchaseReturn->status,
                'order_tax'           => $purchaseReturn->order_tax,
                'shipping_cost'       => $purchaseReturn->shipping_cost,
                'discount_amount'     => $purchaseReturn->discount_amount,
                'discount_type'       => $purchaseReturn->discount_type,
                'note'                => $purchaseReturn->note,
            ],
            'items'            => $itemRows,
            'items_count'      => $groupedItems->count(),
            'barcode'          => 'data:image/png;base64,' . $barcodeBase64,
            'payment_summary'  => $paymentSummary,
            'total_quantity'   => $totalQuantity,
            'total_unit_price' => $totalUnitPrice,
            'total_discount'   => $totalDiscount,
            'received_amount'  => $receivedAmount,
            'due_amount'       => $dueAmount,
        ]);
    }

    public function download(PurchaseReturn $purchaseReturn)
    {
        $pdf = $this->generatePdf($purchaseReturn);

        if (request()->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $purchaseReturn->full_invoice_number . '.pdf"',
            ]);
        }


        return $pdf->download("{$purchaseReturn->full_invoice_number}.pdf");
    }

    public function generatePdf(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues.attribute',
            'payments',
            'tenant'
        ]);
        $logo = to_cdn_url(config('settings.application.company_logo'));
        $paidAmount = $purchaseReturn->getDueAmountAttribute();
        $dueAmount = $purchaseReturn->grand_total - $paidAmount;
        $data = [
            'purchaseReturn' => $purchaseReturn,
            'supplier' => $purchaseReturn->contact,
            'items' => $purchaseReturn->items,
            'payments' => $purchaseReturn->payments,
            'tenant' => $purchaseReturn->tenant,
            'logo' => $logo,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
        ];

        return Pdf::loadView('pdf.purchase-return-invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }
}
