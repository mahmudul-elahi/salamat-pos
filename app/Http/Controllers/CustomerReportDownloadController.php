<?php

namespace App\Http\Controllers;

use App\Exceptions\GeneralException;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReportDownloadController extends Controller
{
    /**
     * @throws GeneralException
     */
    public function download(Request $request, $contactId)
    {
        $contactInfo = Contact::query()->find($contactId);
        if (!$contactInfo) {
            throw new GeneralException('Invalid contact info');
        }

        $search = $request->query('search');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $shop = $request->query('shop');
        $tenantId = $isAppAdmin ? $shop : tenant()->id;

        // App-admin + shop selected => filter to that one shop only.
        // App-admin + no shop selected => see all tenants combined.
        // Normal user => always filtered to their own tenant.
        $applyTenantFilter = !$isAppAdmin || ($isAppAdmin && $tenantId);

        $sales = Sale::query()
            ->when($applyTenantFilter, fn($q) => $q->where('tenant_id', $tenantId))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                'created_at',
                DB::raw("'' as note"),
                DB::raw("'' as ac_name"),
                DB::raw("'Sale' as type"),
                'grand_total as debit',
                DB::raw('0 as credit')
            )
            ->where('contact_id', $contactId);

        $saleReturns = SaleReturn::query()
            ->when($applyTenantFilter, fn($q) => $q->where('tenant_id', $tenantId))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                'created_at',
                DB::raw("'' as note"),
                DB::raw("'' as ac_name"),
                DB::raw("'Sale Return' as type"),
                DB::raw('0 as debit'),
                'grand_total as credit'
            )
            ->where('contact_id', $contactId);

        $purchases = Purchase::query()
            ->when($applyTenantFilter, fn($q) => $q->where('tenant_id', $tenantId))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                'created_at',
                DB::raw("'' as note"),
                DB::raw("'' as ac_name"),
                DB::raw("'Purchase' as type"),
                DB::raw('0 as debit'),
                'grand_total as credit'
            )
            ->where('contact_id', $contactId);

        $purchaseReturns = PurchaseReturn::query()
            ->when($applyTenantFilter, fn($q) => $q->where('tenant_id', $tenantId))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                'created_at',
                DB::raw("'' as note"),
                DB::raw("'' as ac_name"),
                DB::raw("'Purchase Return' as type"),
                'grand_total as debit',
                DB::raw('0 as credit')
            )
            ->where('contact_id', $contactId);

        $exchangePurchases = Purchase::query()
            ->when($applyTenantFilter, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereHas('items', fn($q) => $q->where('purchase_type', 'exchange'))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                'created_at',
                DB::raw("'' as note"),
                DB::raw("'' as ac_name"),
                DB::raw("'Exchange' as type"),
                DB::raw('grand_total as debit'),
                DB::raw('0 as credit')
            )
            ->where('contact_id', $contactId);

        $ledgers = Ledger::query()
            ->when($applyTenantFilter, fn($q) => $q->where('ledgers.tenant_id', $tenantId))
            ->leftJoin('payment_methods', 'ledgers.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'ledgers.id',
                'ledgers.date',
                'ledgers.full_invoice_number',
                'ledgers.created_at',
                DB::raw("IFNULL(payment_methods.name, '') as ac_name"),
                'ledgers.note as note',
                DB::raw("
                CASE
                    WHEN type = 'payment_received' THEN 'Payment Received'
                    WHEN type = 'payment_sent' THEN 'Payment Sent'
                    WHEN type = 'return_payment_sent' THEN 'Return Payment Sent'
                    ELSE type
                END as type
            "),
                'ledgers.debit',
                'ledgers.credit'
            )
            ->where('ledgers.contact_id', $contactId);

        $ledgerUnion = $sales->unionAll($saleReturns)
            ->unionAll($exchangePurchases)
            ->unionAll($purchases)
            ->unionAll($purchaseReturns)
            ->unionAll($ledgers);

        $totals = DB::query()
            ->fromSub($ledgerUnion, 'total_sum')
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $ledgerEntries = DB::query()
            ->fromSub($ledgerUnion, 'combined')
            ->when($search, fn($q) => $q->where('full_invoice_number', 'like', "%{$search}%"))
            ->select('*')
            ->selectRaw('SUM(debit - credit) OVER (ORDER BY date ASC, created_at ASC) as balance')
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $tenantSetting = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $data = [
            'contactInfo'    => $contactInfo,
            'ledgerEntries'  => $ledgerEntries,
            'totalDebit'     => $totals->total_debit ?? 0,
            'totalCredit'    => $totals->total_credit ?? 0,
            'balance'        => ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0),
            'dateRange'      => now()->format('Y-m-d'),
            'tenantSettings' => $tenantSetting,
        ];

        $pdf = Pdf::loadView('pdf.customer-ledger-invoice', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('customer-ledger-report.pdf');
    }
}
