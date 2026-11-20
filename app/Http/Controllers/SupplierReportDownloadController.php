<?php

namespace App\Http\Controllers;

use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Supplier\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SupplierReportDownloadController extends Controller
{
    public function download(Request $request, $supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);

        // Date range (static or from request)
        $startDate = $request->query('start_date', '2023-11-01');
        $endDate = $request->query('end_date', '2023-11-30');

        // Static ledger entries - example data matching your sample image
        $ledgerEntries = [
            [
                'date' => '2023-11-01',
                'type' => 'Purchase',
                'transaction_mode' => 'Credit Purchase',
                'account_name' => 'Account A',
                'narration' => 'Sample narration 1',
                'cheque_no' => '',
                'ref_no' => '',
                'invoice_number' => 'PS-000306',
                'debit' => 0.00,
                'credit' => 0.00,
                'balance' => 0.00,
            ],
            [
                'date' => '2023-11-06',
                'type' => 'Purchase',
                'transaction_mode' => 'Credit Purchase',
                'account_name' => 'Account B',
                'narration' => 'Sample narration 2',
                'cheque_no' => '',
                'ref_no' => '',
                'invoice_number' => 'PS-000407',
                'debit' => 0.00,
                'credit' => 259500.00,
                'balance' => 259500.00,
            ],
            // ... add as many rows as you want
        ];

        // Calculate totals manually or statically
        $totalDebit = array_sum(array_column($ledgerEntries, 'debit'));
        $totalCredit = array_sum(array_column($ledgerEntries, 'credit'));
        $balance = $totalDebit - $totalCredit;

        $data = [
            'supplier' => $supplier,
            'ledgerEntries' => $ledgerEntries,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balance' => $balance,
            'dateRange' => "$startDate to $endDate",
            'shop' => '4A-018A Level: 4 Block: A, Jamuna Future Park, Dhaka, Bangladesh',
            'companyName' => 'TEKZO',
            'companyWebsite' => 'www.tekzo.com.bd',
            'contactNumber' => '+880 1897-715226',
            'email' => 'info@tekzo.com.bd',
            'address' => 'House 96, 2nd Floor (2A) 701 Faser Tek, Vatara, Dhaka1212, Gulshan, Dhaka-1212',
        ];

        $pdf = Pdf::loadView('pdf.supplier-ledger-invoice', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        if ($request->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="supplier-ledger-invoice.pdf"',
            ]);
        }

        return $pdf->download('supplier-ledger-invoice.pdf');
    }

    public function generatePdf(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
            'items.productVariant.attributeValues.attribute',
            'payments',
            'tenant',
        ]);
        $logo = config('settings.application.company_logo');
        $receivedAmount = $purchase->getTransactionAmountAttribute();
        $dueAmount = $purchase->grand_total - $receivedAmount;
        $data = [
            'purchase' => $purchase,
            'supplier' => $purchase->supplier,
            'items' => $purchase->items,
            'payments' => $purchase->payments,
            'tenant' => $purchase->tenant,
            'logo' => $logo,
            'received_amount' => $receivedAmount,
            'due_amount' => $dueAmount,
        ];

        return Pdf::loadView('pdf.supplier-ledger-invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }
}
