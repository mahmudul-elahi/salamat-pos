<?php

namespace App\Http\Controllers\Pos\Report;

use App\Http\Controllers\Controller;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use Illuminate\Support\Facades\DB;

class CustomerReportController extends Controller
{
    /**
     * Contacts whose net balance falls on the given side, as a subquery so the
     * ids never have to be pulled into PHP.
     *
     * Mirrors exactly the six sources index() sums per page — sales and
     * purchase returns debit, sale returns and purchases credit, plus manual
     * ledger rows — so the filter can never disagree with the numbers shown.
     *
     * @param  string  $side  'debit' (balance > 0) or 'credit' (balance < 0)
     */
    private function contactIdsByBalanceSide(?int $tenantId, string $side)
    {
        $forTenant = fn ($query) => $query->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $sales = $forTenant(Sale::query())
            ->select('contact_id', 'grand_total as debit', DB::raw('0 as credit'));

        $saleReturns = $forTenant(SaleReturn::query())
            ->select('contact_id', DB::raw('0 as debit'), 'grand_total as credit');

        $purchases = $forTenant(Purchase::query())
            ->select('contact_id', DB::raw('0 as debit'), 'grand_total as credit');

        $purchaseReturns = $forTenant(PurchaseReturn::query())
            ->select('contact_id', 'grand_total as debit', DB::raw('0 as credit'));

        $exchangePurchases = $forTenant(Purchase::query())
            ->whereHas('items', fn ($q) => $q->where('purchase_type', 'exchange'))
            ->select('contact_id', DB::raw('grand_total as debit'), DB::raw('0 as credit'));

        $ledgers = $forTenant(Ledger::query())
            ->select('contact_id', 'debit', 'credit');

        $union = $sales->unionAll($saleReturns)
            ->unionAll($exchangePurchases)
            ->unionAll($purchases)
            ->unionAll($purchaseReturns)
            ->unionAll($ledgers);

        return DB::query()
            ->fromSub($union, 'combined')
            ->select('contact_id')
            ->groupBy('contact_id')
            ->havingRaw(
                $side === 'debit'
                    ? 'SUM(debit) - SUM(credit) > 0'
                    : 'SUM(debit) - SUM(credit) < 0'
            );
    }

    public function index()
    {
        $search = request('search');
        $shop = request('shop');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId = $isAppAdmin ? $shop : tenant()->id;

        // 'debit' = the balance falls on the debit side (they owe us), 'credit'
        // = the credit side (we owe them). Anything else means no filter.
        $balanceType = request('balance_type');

        $contacts = Contact::query()
            // ->when($isAppAdmin && $tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(
                $search,
                fn ($q) => $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('business_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
            )
            // Balances are worked out per page below, so a balance-side filter
            // can't be a HAVING on that — narrow the contacts to the ones whose
            // balance leans the right way before paginating.
            ->when(
                in_array($balanceType, ['debit', 'credit'], true),
                fn ($q) => $q->whereIn('contacts.id', $this->contactIdsByBalanceSide($tenantId, $balanceType))
            )
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));

        $contactIds = collect($contacts->items())->pluck('id');

        $balances = collect();

        if ($contactIds->isNotEmpty()) {
            $sales = Sale::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', 'grand_total as debit', DB::raw('0 as credit'));

            $saleReturns = SaleReturn::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', DB::raw('0 as debit'), 'grand_total as credit');

            $purchases = Purchase::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', DB::raw('0 as debit'), 'grand_total as credit');

            $purchaseReturns = PurchaseReturn::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', 'grand_total as debit', DB::raw('0 as credit'));

            $exchangePurchases = Purchase::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereHas('items', fn ($q) => $q->where('purchase_type', 'exchange'))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', DB::raw('grand_total as debit'), DB::raw('0 as credit'));

            $ledgers = Ledger::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('contact_id', $contactIds)
                ->select('contact_id', 'debit', 'credit');

            $ledgerUnion = $sales->unionAll($saleReturns)
                ->unionAll($exchangePurchases)
                ->unionAll($purchases)
                ->unionAll($purchaseReturns)
                ->unionAll($ledgers);

            $balances = DB::query()
                ->fromSub($ledgerUnion, 'combined')
                ->select('contact_id')
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->groupBy('contact_id')
                ->get()
                ->keyBy('contact_id');
        }

        $contacts->getCollection()->transform(function ($contact) use ($balances) {
            $row = $balances->get($contact->id);
            $debit = $row->total_debit ?? 0;
            $credit = $row->total_credit ?? 0;

            return [
                ...$contact->toArray(),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $debit - $credit,
            ];
        });

        return response()->json($contacts->toArray());
    }

    public function ledgerReport($contactId)
    {

        $perPage = request('per_page', 50);
        $search = request('search');
        $shop = request('shop');
        $balance = 0;

        $isAppAdmin = auth()->user()->isAppAdmin();

        if ($isAppAdmin) {
            $tenantId = $shop ?: null;
        } else {
            $tenantId = tenant()->id;
        }

        // Sale/Purchase/Return invoices can be settled across several
        // payments in different methods, so there's no single method to show
        // per row - only the Ledger rows below (a single payment received/
        // sent/refunded in one go) have one. The other five branches select
        // a null placeholder purely to keep the UNION's column count/order
        // aligned with the Ledger branch's real payment_method column.
        $sales = Sale::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->select('id', 'date', 'created_at', 'full_invoice_number', DB::raw("'Sale' as type"), 'grand_total as debit', DB::raw('0 as credit'), DB::raw('NULL as payment_method'), DB::raw('NULL as payment_method_logo'))
            ->where('contact_id', $contactId);

        $saleReturns = SaleReturn::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->select('id', 'date', 'created_at', 'full_invoice_number', DB::raw("'Sale Return' as type"), DB::raw('0 as debit'), 'grand_total as credit', DB::raw('NULL as payment_method'), DB::raw('NULL as payment_method_logo'))
            ->where('contact_id', $contactId);

        $purchases = Purchase::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->select('id', 'date', 'created_at', 'full_invoice_number', DB::raw("'Purchase' as type"), DB::raw('0 as debit'), 'grand_total as credit', DB::raw('NULL as payment_method'), DB::raw('NULL as payment_method_logo'))
            ->where('contact_id', $contactId);

        $purchaseReturns = PurchaseReturn::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->select('id', 'date', 'created_at', 'full_invoice_number', DB::raw("'Purchase Return' as type"), 'grand_total as debit', DB::raw('0 as credit'), DB::raw('NULL as payment_method'), DB::raw('NULL as payment_method_logo'))
            ->where('contact_id', $contactId);

        $exchangePurchases = Purchase::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereHas('items', fn ($q) => $q->where('purchase_type', 'exchange'))
            ->select(
                'id',
                'date',
                'created_at',
                'full_invoice_number',
                DB::raw("'Exchange' as type"),
                DB::raw('grand_total as debit'),
                DB::raw('0 as credit'),
                DB::raw('NULL as payment_method'),
                DB::raw('NULL as payment_method_logo')
            )
            ->where('contact_id', $contactId);

        $ledgers = Ledger::query()
            // Qualified as ledgers.tenant_id - payment_methods (joined below)
            // has its own tenant_id column too, so the unqualified form was
            // ambiguous to MySQL once that join was added.
            ->when($tenantId, fn ($q) => $q->where('ledgers.tenant_id', $tenantId))
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'ledgers.payment_method_id')
            ->select(
                'ledgers.id',
                'ledgers.date',
                'ledgers.created_at',
                'ledgers.full_invoice_number',
                DB::raw("
                CASE
                    WHEN ledgers.type = 'payment_received' THEN 'Payment Received'
                    WHEN ledgers.type = 'payment_sent' THEN 'Payment Sent'
                    WHEN ledgers.type = 'return_payment_sent' THEN 'Return Payment Sent'
                    ELSE ledgers.type
                END as type
            "),
                'ledgers.debit',
                'ledgers.credit',
                'payment_methods.name as payment_method',
                'payment_methods.logo as payment_method_logo'
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

        $data = DB::query()
            ->fromSub($ledgerUnion, 'combined')
            ->when($search, fn ($q) => $q->where('full_invoice_number', 'like', "%{$search}%"))
            ->select('*')
            // ->selectRaw('SUM(debit - credit) OVER (ORDER BY date ASC, created_at ASC) as balance')
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage)
            ->through(function ($item, $key) use (&$balance) {
                $balance += ($item->debit - $item->credit);

                return [
                    ...(array) $item,
                    'sl' => $key + 1,
                    'balance' => $balance,
                ];
            });

        return response()->json([
            ...$data->toArray(),
            'grand_total' => [
                'debit' => $totals->total_debit ?? 0,
                'credit' => $totals->total_credit ?? 0,
                'balance' => ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0),
            ],
        ]);
    }
}
