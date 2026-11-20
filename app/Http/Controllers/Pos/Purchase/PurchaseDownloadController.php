<?php

namespace App\Http\Controllers\Pos\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Pos\Purchase\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Milon\Barcode\DNS1D;

class PurchaseDownloadController extends Controller
{
    public function download(Purchase $purchase)
    {
        $pdf = $this->generatePdf($purchase);

        if (request()->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$purchase->full_invoice_number.'.pdf"',
            ]);
        }

        return $pdf->download("{$purchase->full_invoice_number}.pdf");
    }

    public function invoiceData(Purchase $purchase): JsonResponse
    {
        $purchase->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues',
            'payments.paymentMethod',
        ]);

        $supplier = $purchase->contact;

        $receivedAmount = $purchase->getTransactionAmountAttribute();
        $dueAmount = max(0, $purchase->grand_total - $receivedAmount);
        $totalQuantity = $purchase->items->sum('quantity');

        $totalUnitPrice = $purchase->items->sum(fn ($item) => $item->unit_price * $item->quantity);

        $totalDiscount = $purchase->items->sum(fn ($item) => (float) ($item->discount_amount ?? 0));

        $barcodeBase64 = (new DNS1D)->getBarcodePNG($purchase->full_invoice_number, 'C128', 1, 40);

        $groupedItems = $purchase->items->groupBy('product_variant_id');

        $itemRows = $groupedItems->map(function ($items) {
            $first = $items->first();

            return [
                'product_name' => $first->product?->product_name,
                'product_image' => to_cdn_url($first->product?->product_image),
                'attributes' => $first->productVariant?->attributeValues?->pluck('value') ?? [],
                'total_qty' => $items->sum('quantity'),
                'lines' => $items->map(fn ($item) => [
                    'serial_number' => $item->serial_number,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => (float) ($item->discount_amount ?? 0),
                    'line_total' => ($item->unit_price * $item->quantity) - ($item->discount_amount ?? 0),
                ])->values(),
            ];
        })->values();

        $paymentSummary = $purchase->payments
            ->groupBy('payment_method_id')
            ->map(fn ($txns) => [
                'method' => optional($txns->first()->paymentMethod)->name,
                'amount' => $txns->sum('amount'),
            ])->values();

        return response()->json([
            'supplier' => [
                'name' => $supplier?->name,
                'phone' => $supplier?->phone,
                'email' => $supplier?->email,
                'address' => $supplier?->address,
            ],
            'purchase' => [
                'date' => $purchase->date,
                'full_invoice_number' => $purchase->full_invoice_number,
                'grand_total' => $purchase->grand_total,
                'status' => $purchase->status,
                'order_tax' => $purchase->order_tax,
                'shipping_cost' => $purchase->shipping_cost,
                'discount_amount' => $purchase->discount_amount,
                'discount_type' => $purchase->discount_type,
                'note' => $purchase->note,
            ],
            'items' => $itemRows,
            'items_count' => $groupedItems->count(),
            'barcode' => 'data:image/png;base64,'.$barcodeBase64,
            'payment_summary' => $paymentSummary,
            'total_quantity' => $totalQuantity,
            'total_unit_price' => $totalUnitPrice,
            'total_discount' => $totalDiscount,
            'received_amount' => $receivedAmount,
            'due_amount' => $dueAmount,
        ]);
    }

    public function generatePdf(Purchase $purchase)
    {
        $purchase->load([
            'contact',
            'items.product',
            'items.productVariant.attributeValues',
            'payments',
            'tenant',
        ]);

        $receivedAmount = $purchase->getTransactionAmountAttribute();
        $dueAmount = $purchase->grand_total - $receivedAmount;
        $totalQuantity = $purchase->items->sum('quantity');

        $data = [
            'purchase' => $purchase,
            'taka' => public_path('assets/images/taka.png'),
            'barcode' => 'data:image/png;base64,'.(new DNS1D)->getBarcodePNG($purchase->full_invoice_number, 'C128'),
            'contact' => $purchase->contact,
            'items' => $purchase->items,
            'totalQuantity' => $totalQuantity,
            'payments' => $purchase->payments,
            'tenant' => $purchase->tenant,
            'logo' => to_cdn_url(config('settings.application.company_logo')),
            'grand_total' => $purchase->grand_total,
            'received_amount' => $receivedAmount,
            'due_amount' => $dueAmount,
        ];

        return Pdf::loadView('pdf.purchase-invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }
}
