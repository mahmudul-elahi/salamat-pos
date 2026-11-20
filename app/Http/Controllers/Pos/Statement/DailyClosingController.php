<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Http\Controllers\Controller;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyClosingController extends Controller
{
    private function extractFilters(Request $request): array
    {
        return [
            'contact' => $request->get('party', $request->get('contact')),
            'seller' => $request->get('seller'),
            'payment_method' => $request->get('payment_method'),
            'status' => $request->get('status'),
        ];
    }

    public function index(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $warehouse = $isAppAdmin ? $request->get('warehouse') : tenant()->id;
        $filters = $this->extractFilters($request);

        $today = Carbon::now('Asia/Dhaka')->toDateString();

        $openingBalance = $this->calculateOpeningBalance($today, $warehouse);
        $mergedToday = $this->getTodayDataQuery($today, $warehouse, $filters);

        // Build the same filtered base query, used both for totals AND pagination
        $baseQuery = DB::query()
            ->fromSub($mergedToday, 'combined')
            ->when($search, fn ($q) => $q->whereRaw('LOWER(full_invoice_number) LIKE ?', ['%'.strtolower($search).'%']));

        // ---- Grand total over the FULL filtered set (no pagination) ----
        $grandTotals = DB::query()
            ->fromSub($baseQuery, 'gt')
            ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
            ->first();

        $grandBalance = $openingBalance + ($grandTotals->total_debit - $grandTotals->total_credit);

        // ---- Paginated data for display ----
        $data = (clone $baseQuery)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        // Running balance shown per row should reflect position in FULL list,
        // not just within the current page — see note below.
        $currentBalance = $openingBalance;
        $collection = $data->getCollection()->map(function ($item) use (&$currentBalance) {
            $currentBalance += ($item->debit - $item->credit);
            $item->balance = $currentBalance;

            return $item;
        });

        $openingRow = (object) [
            'id' => '-',
            'date' => '-',
            'full_invoice_number' => '-',
            'type' => 'Opening Balance',
            'total' => '0.00',
            'debit' => '0.00',
            'credit' => '0.00',
            'balance' => $openingBalance,
            'contact_name' => null,
            'contact_image' => null,
            'payment_method' => null,
            'payment_method_logo' => null,
            'created_by_name' => null,
            'seller_name' => null,
            'seller_image' => null,
            'status' => null,
            'model_type' => null,
            'tenant_name' => null,
            'sl' => 1,
        ];

        $finalCollection = collect([$openingRow])->concat($collection)->map(function ($item, $key) {
            $item->sl = $key + 1;

            return $item;
        });

        $data->setCollection($finalCollection);

        return response()->json([
            ...$data->toArray(),
            'grand_total' => ['balance' => $grandBalance],
        ]);
    }

    /**
     * Complete Today's Data Logic
     */
    /**
     * Correlated subquery returning "Product Name (Variant)" for every line
     * item on an invoice, joined with '; ' — rendered as a sub-line under
     * the invoice number in the Daily Closing PDF.
     */
    private function productsSubquery(string $itemsTable, string $fkColumn, string $invoiceAlias): string
    {
        return "(SELECT GROUP_CONCAT(DISTINCT CONCAT(
                    pd.product_name,
                    IFNULL(CONCAT(' (', (
                        SELECT GROUP_CONCAT(av.value ORDER BY av.id SEPARATOR '/')
                        FROM variant_attribute_values vav
                        INNER JOIN attribute_values av ON av.id = vav.attribute_value_id
                        WHERE vav.product_variant_id = it.product_variant_id
                    ), ')'), '')
                ) SEPARATOR '; ')
                FROM {$itemsTable} it
                INNER JOIN products pd ON pd.id = it.product_id
                WHERE it.deleted_at IS NULL AND it.{$fkColumn} = {$invoiceAlias}.id)";
    }

    private function getTodayDataQuery($today, $warehouse, array $filters = [])
    {
        $contact = $filters['contact'] ?? null;
        $seller = $filters['seller'] ?? null;
        $paymentMethod = $filters['payment_method'] ?? null;
        $status = $filters['status'] ?? null;
        $statusList = $status ? explode(',', $status) : [];

        $purchases = DB::table('purchases as p')
            ->whereNull('p.deleted_at')
            ->whereDate('p.date', $today)
            ->when($warehouse, fn ($q) => $q->where('p.tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('p.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereIn('p.status', $statusList))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('transactions as t')
                        ->whereColumn('t.model_id', 'p.id')
                        ->where('t.model_type', Purchase::class)
                        ->where('t.payment_method_id', $paymentMethod);
                })
            )
            ->select(
                'p.id',
                'p.date',
                'p.full_invoice_number',
                DB::raw("'Purchase' as type"),
                'p.grand_total as total',
                DB::raw('0 as debit'),
                DB::raw('0 as credit'),
                'p.created_at',
                'p.status',
                DB::raw("'purchase' as model_type")
            )
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = p.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = p.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = p.created_by) as created_by_name')
            ->selectRaw(
                "(SELECT GROUP_CONCAT(DISTINCT pm.name SEPARATOR ', ') FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = p.id) as payment_method",
                [Purchase::class]
            )
            ->selectRaw(
                '(SELECT pm.logo FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = p.id LIMIT 1) as payment_method_logo',
                [Purchase::class]
            )
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = p.tenant_id) as tenant_name')
            ->selectRaw($this->productsSubquery('purchase_items', 'purchase_id', 'p').' as products');

        // 2. Purchase Returns
        $pReturns = DB::table('purchase_returns as pr')
            ->whereNull('pr.deleted_at')
            ->whereDate('pr.date', $today)
            ->when($warehouse, fn ($q) => $q->where('pr.tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('pr.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereIn('pr.status', $statusList))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('transactions as t')
                        ->whereColumn('t.model_id', 'pr.id')
                        ->where('t.model_type', PurchaseReturn::class)
                        ->where('t.payment_method_id', $paymentMethod);
                })
            )
            ->select(
                'pr.id',
                'pr.date',
                'pr.full_invoice_number',
                DB::raw("'Purchase Return' as type"),
                'pr.grand_total as total',
                DB::raw('0 as debit'),
                DB::raw('0 as credit'),
                'pr.created_at',
                'pr.status',
                DB::raw("'purchase_return' as model_type")
            )
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = pr.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = pr.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = pr.created_by) as created_by_name')
            ->selectRaw(
                "(SELECT GROUP_CONCAT(DISTINCT pm.name SEPARATOR ', ') FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = pr.id) as payment_method",
                [PurchaseReturn::class]
            )
            ->selectRaw(
                '(SELECT pm.logo FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = pr.id LIMIT 1) as payment_method_logo',
                [PurchaseReturn::class]
            )
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = pr.tenant_id) as tenant_name')
            ->selectRaw($this->productsSubquery('purchase_return_items', 'purchase_return_id', 'pr').' as products');

        $sales = DB::table('sales as s')
            ->whereNull('s.deleted_at')
            ->whereDate('s.date', $today)
            ->when($warehouse, fn ($q) => $q->where('s.tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('s.contact_id', $contact))
            ->when($seller, fn ($q) => $q->where('s.seller_id', $seller))
            ->when($status, fn ($q) => $q->whereIn('s.status', $statusList))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('transactions as t')
                        ->whereColumn('t.model_id', 's.id')
                        ->where('t.model_type', Sale::class)
                        ->where('t.payment_method_id', $paymentMethod);
                })
            )
            ->select(
                's.id',
                's.date',
                's.full_invoice_number',
                DB::raw("'Sale' as type"),
                's.grand_total as total',
                DB::raw('0 as debit'),
                DB::raw('0 as credit'),
                's.created_at',
                's.status',
                DB::raw("'sale' as model_type")
            )
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = s.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = s.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = s.created_by) as created_by_name')
            ->selectRaw(
                "(SELECT GROUP_CONCAT(DISTINCT pm.name SEPARATOR ', ') FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = s.id) as payment_method",
                [Sale::class]
            )
            ->selectRaw(
                '(SELECT pm.logo FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = s.id LIMIT 1) as payment_method_logo',
                [Sale::class]
            )
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = s.seller_id) as seller_name')
            ->selectRaw('(SELECT u.profile_photo FROM users u WHERE u.id = s.seller_id) as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = s.tenant_id) as tenant_name')
            ->selectRaw($this->productsSubquery('sale_items', 'sale_id', 's').' as products');

        // 4. Sale Returns
        $sReturns = DB::table('sale_returns as sr')
            ->whereNull('sr.deleted_at')
            ->whereDate('sr.date', $today)
            ->when($warehouse, fn ($q) => $q->where('sr.tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('sr.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereIn('sr.status', $statusList))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('transactions as t')
                        ->whereColumn('t.model_id', 'sr.id')
                        ->where('t.model_type', SaleReturn::class)
                        ->where('t.payment_method_id', $paymentMethod);
                })
            )
            ->select(
                'sr.id',
                'sr.date',
                'sr.full_invoice_number',
                DB::raw("'Sale Return' as type"),
                'sr.grand_total as total',
                DB::raw('0 as debit'),
                DB::raw('0 as credit'),
                'sr.created_at',
                'sr.status',
                DB::raw("'sale_return' as model_type")
            )
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = sr.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = sr.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = sr.created_by) as created_by_name')
            ->selectRaw(
                "(SELECT GROUP_CONCAT(DISTINCT pm.name SEPARATOR ', ') FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = sr.id) as payment_method",
                [SaleReturn::class]
            )
            ->selectRaw(
                '(SELECT pm.logo FROM transactions t
                    INNER JOIN payment_methods pm ON pm.id = t.payment_method_id
                    WHERE t.model_type = ? AND t.model_id = sr.id LIMIT 1) as payment_method_logo',
                [SaleReturn::class]
            )
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = sr.tenant_id) as tenant_name')
            ->selectRaw($this->productsSubquery('sale_return_items', 'sale_return_id', 'sr').' as products');

        // 5. Ledgers — debit/credit already exist on the table itself
        $ledgers = DB::table('ledgers as l')
            ->whereDate('l.date', $today)
            ->when($warehouse, fn ($q) => $q->where('l.tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('l.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereRaw('0=1'))
            ->when($paymentMethod, fn ($q) => $q->where('l.payment_method_id', $paymentMethod))
            ->select(
                'l.id',
                'l.date',
                'l.full_invoice_number',
                DB::raw("CASE WHEN IFNULL(l.credit, 0) > 0 THEN 'Payment Received' WHEN IFNULL(l.debit, 0) > 0 THEN 'Payment Sent' ELSE 'Ledger' END as type"),
                DB::raw('IFNULL(l.debit, 0) + IFNULL(l.credit, 0) as total'),
                DB::raw('IFNULL(l.credit, 0) as debit'),
                DB::raw('IFNULL(l.debit, 0) as credit'),
                'l.created_at',
                DB::raw('NULL as status'),
                DB::raw('NULL as model_type')
            )
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = l.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = l.contact_id) as contact_image')
            ->selectRaw('NULL as created_by_name')
            ->selectRaw('(SELECT pm.name FROM payment_methods pm WHERE pm.id = l.payment_method_id) as payment_method')
            ->selectRaw('(SELECT pm.logo FROM payment_methods pm WHERE pm.id = l.payment_method_id) as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = l.tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        // 6. Expenses, Withdraws, Investments
        $expenses = DB::table('expenses')
            ->whereDate('expenses.date', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->whereRaw('0=1'))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereRaw('0=1'))
            ->when($paymentMethod, fn ($q) => $q->where('payment_method_id', $paymentMethod))
            ->select('id', 'date', DB::raw("CONCAT('EXP-', id) as full_invoice_number"), DB::raw("'Expense' as type"), 'amount as total', DB::raw('0 as debit'), 'amount as credit', 'created_at', DB::raw('NULL as status'), DB::raw('NULL as model_type'))
            ->selectRaw('NULL as contact_name')
            ->selectRaw('NULL as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = expenses.created_by) as created_by_name')
            ->selectRaw('(SELECT pm.name FROM payment_methods pm WHERE pm.id = expenses.payment_method_id) as payment_method')
            ->selectRaw('(SELECT pm.logo FROM payment_methods pm WHERE pm.id = expenses.payment_method_id) as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = expenses.tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        $withdraws = DB::table('incomes')
            ->whereDate('incomes.date', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('incomes.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereRaw('0=1'))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('ledgers as lg')
                        ->whereColumn('lg.id', 'incomes.ledger_id')
                        ->where('lg.payment_method_id', $paymentMethod);
                })
            )
            ->select('id', 'date', DB::raw("CONCAT('WTH-', id) as full_invoice_number"), DB::raw("'Withdraw' as type"), 'amount as total', DB::raw('0 as debit'), 'amount as credit', 'created_at', DB::raw('NULL as status'), DB::raw('NULL as model_type'))
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = incomes.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = incomes.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = incomes.created_by) as created_by_name')
            ->selectRaw('(SELECT pm.name FROM payment_methods pm INNER JOIN ledgers lg ON lg.payment_method_id = pm.id WHERE lg.id = incomes.ledger_id) as payment_method')
            ->selectRaw('(SELECT pm.logo FROM payment_methods pm INNER JOIN ledgers lg ON lg.payment_method_id = pm.id WHERE lg.id = incomes.ledger_id) as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = incomes.tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        $investments = DB::table('investments')
            ->whereDate('investments.date', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->when($contact, fn ($q) => $q->where('investments.contact_id', $contact))
            ->when($seller, fn ($q) => $q->whereRaw('0=1'))
            ->when($status, fn ($q) => $q->whereRaw('0=1'))
            ->when(
                $paymentMethod,
                fn ($q) => $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->selectRaw('1')
                        ->from('ledgers as lg')
                        ->whereColumn('lg.id', 'investments.ledger_id')
                        ->where('lg.payment_method_id', $paymentMethod);
                })
            )
            ->select('id', 'date', DB::raw("CONCAT('INV-', id) as full_invoice_number"), DB::raw("'Deposit' as type"), 'amount as total', 'amount as debit', DB::raw('0 as credit'), 'created_at', DB::raw('NULL as status'), DB::raw('NULL as model_type'))
            ->selectRaw('(SELECT c.name FROM contacts c WHERE c.id = investments.contact_id) as contact_name')
            ->selectRaw('(SELECT c.profile_picture FROM contacts c WHERE c.id = investments.contact_id) as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = investments.created_by) as created_by_name')
            ->selectRaw('(SELECT pm.name FROM payment_methods pm INNER JOIN ledgers lg ON lg.payment_method_id = pm.id WHERE lg.id = investments.ledger_id) as payment_method')
            ->selectRaw('(SELECT pm.logo FROM payment_methods pm INNER JOIN ledgers lg ON lg.payment_method_id = pm.id WHERE lg.id = investments.ledger_id) as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = investments.tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        // 8. Cash Transfer OUT — money leaves this warehouse (credit)
        $cashTransferOut = DB::table('cash_transfers as ct')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->whereDate('ct.transfer_date', $today)
            ->when(
                $warehouse,
                fn ($q) => $q->where('ct.from_tenant_id', $warehouse),
                fn ($q) => $q->whereRaw('0=1')
            )
            ->when($contact || $seller || $status || $paymentMethod, fn ($q) => $q->whereRaw('0=1'))
            ->select(
                'ct.id',
                'ct.transfer_date as date',
                'ct.reference_no as full_invoice_number',
                DB::raw("'Cash Transfer Out' as type"),
                'ct.amount as total',
                DB::raw('0 as debit'),
                'ct.amount as credit',
                'ct.created_at',
                DB::raw('NULL as status'),
                DB::raw('NULL as model_type')
            )
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = ct.to_tenant_id) as contact_name')
            ->selectRaw('NULL as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = ct.created_by) as created_by_name')
            ->selectRaw("'Cash Transfer' as payment_method")
            ->selectRaw('NULL as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = ct.from_tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        // 9. Cash Transfer IN — money arrives at this warehouse (debit)
        $cashTransferIn = DB::table('cash_transfers as ct')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->whereDate('ct.transfer_date', $today)
            ->when(
                $warehouse,
                fn ($q) => $q->where('ct.to_tenant_id', $warehouse),
                fn ($q) => $q->whereRaw('0=1')
            )
            ->when($contact || $seller || $status || $paymentMethod, fn ($q) => $q->whereRaw('0=1'))
            ->select(
                'ct.id',
                'ct.transfer_date as date',
                'ct.reference_no as full_invoice_number',
                DB::raw("'Cash Transfer In' as type"),
                'ct.amount as total',
                'ct.amount as debit',
                DB::raw('0 as credit'),
                'ct.created_at',
                DB::raw('NULL as status'),
                DB::raw('NULL as model_type')
            )
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = ct.from_tenant_id) as contact_name')
            ->selectRaw('NULL as contact_image')
            ->selectRaw('(SELECT u.name FROM users u WHERE u.id = ct.created_by) as created_by_name')
            ->selectRaw("'Cash Transfer' as payment_method")
            ->selectRaw('NULL as payment_method_logo')
            ->selectRaw('NULL as seller_name')
            ->selectRaw('NULL as seller_image')
            ->selectRaw('(SELECT tn.name FROM tenants tn WHERE tn.id = ct.to_tenant_id) as tenant_name')
            ->selectRaw('NULL as products');

        return $purchases->unionAll($pReturns)
            ->unionAll($sales)
            ->unionAll($sReturns)
            ->unionAll($ledgers)
            ->unionAll($expenses)
            ->unionAll($withdraws)
            ->unionAll($investments)
            ->unionAll($cashTransferOut)
            ->unionAll($cashTransferIn);
    }

    private function calculateOpeningBalance($today, $warehouse)
    {

        // Ledger: credit column = debit side, debit column = credit side (same swap as getTodayDataQuery)
        $ledgers = DB::table('ledgers as l')
            ->whereDate('l.date', '<', $today)
            ->when($warehouse, fn ($q) => $q->where('l.tenant_id', $warehouse))
            ->select(
                DB::raw('COALESCE(SUM(IFNULL(l.credit, 0)), 0) as debit'),
                DB::raw('COALESCE(SUM(IFNULL(l.debit, 0)), 0) as credit')
            );

        $expenses = DB::table('expenses')
            ->whereDate('date', '<', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->select(DB::raw('0 as debit'), DB::raw('COALESCE(SUM(amount), 0) as credit'));

        $withdraws = DB::table('incomes')
            ->whereDate('date', '<', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->select(DB::raw('0 as debit'), DB::raw('COALESCE(SUM(amount), 0) as credit'));

        $investments = DB::table('investments')
            ->whereDate('date', '<', $today)
            ->when($warehouse, fn ($q) => $q->where('tenant_id', $warehouse))
            ->select(DB::raw('COALESCE(SUM(amount), 0) as debit'), DB::raw('0 as credit'));

        // Cash Transfer OUT — money leaves this warehouse (credit side)
        $cashTransferOut = DB::table('cash_transfers as ct')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->whereDate('ct.transfer_date', '<', $today)
            ->when(
                $warehouse,
                fn ($q) => $q->where('ct.from_tenant_id', $warehouse),
                fn ($q) => $q->whereRaw('0=1')
            )
            ->select(
                DB::raw('0 as debit'),
                DB::raw('COALESCE(SUM(ct.amount), 0) as credit')
            );

        // Cash Transfer IN — money arrives at this warehouse (debit side)
        $cashTransferIn = DB::table('cash_transfers as ct')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->whereDate('ct.transfer_date', '<', $today)
            ->when(
                $warehouse,
                fn ($q) => $q->where('ct.to_tenant_id', $warehouse),
                fn ($q) => $q->whereRaw('0=1')
            )
            ->select(
                DB::raw('COALESCE(SUM(ct.amount), 0) as debit'),
                DB::raw('0 as credit')
            );

        $merged = $ledgers
            ->unionAll($expenses)
            ->unionAll($withdraws)
            ->unionAll($investments)
            ->unionAll($cashTransferOut)
            ->unionAll($cashTransferIn);

        $totals = DB::query()
            ->fromSub($merged, 'ob')
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        return ($totals->d ?? 0) - ($totals->c ?? 0);
    }

    public function downloadPdf(Request $request)
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();
        $isAppAdmin = auth()->user()->isAppAdmin();
        $warehouse = $isAppAdmin ? $request->get('warehouse') : tenant()->id;
        $filters = $this->extractFilters($request);

        $openingBalance = $this->calculateOpeningBalance($today, $warehouse);
        $collection = $this->getTodayDataQuery($today, $warehouse, $filters)->orderBy('created_at', 'asc')->get();

        $currentBal = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $mappedItems = $collection->map(function ($item) use (&$currentBal, &$totalDebit, &$totalCredit) {
            $totalDebit += $item->debit;
            $totalCredit += $item->credit;
            $currentBal += ($item->debit - $item->credit);

            return [
                'date' => $item->date,
                'particular' => $item->type,
                'invoice' => $item->full_invoice_number,
                'contact_name' => $item->contact_name,
                'contact_image' => $item->contact_image,
                'payment_method' => $item->payment_method,
                'payment_method_logo' => $item->payment_method_logo,
                'created_by_name' => $item->created_by_name,
                'seller_name' => $item->seller_name,
                'seller_image' => $item->seller_image,
                'tenant_name' => $item->tenant_name,
                'products' => $item->products,
                'status' => $item->status,
                'total' => $item->total,
                'debit' => $item->debit,
                'credit' => $item->credit,
                'balance' => $currentBal,
            ];
        })->toArray();

        array_unshift($mappedItems, [
            'date' => '-',
            'particular' => 'Opening Balance',
            'invoice' => '----',
            'contact_name' => null,
            'contact_image' => null,
            'payment_method' => null,
            'payment_method_logo' => null,
            'created_by_name' => null,
            'seller_name' => null,
            'seller_image' => null,
            'tenant_name' => null,
            'products' => null,
            'status' => null,
            'total' => '0.00',
            'debit' => '0.00',
            'credit' => '0.00',
            'balance' => $openingBalance,
        ]);

        $tenantId = $isAppAdmin ? (is_array($warehouse) ? ($warehouse[0] ?? null) : $warehouse) : tenant()->id;
        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $data = [
            'items' => $mappedItems,
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $currentBal,
            'title' => 'Daily Closing Report',
            'date' => $today,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
        ];

        $pdf = Pdf::loadView('pdf.reports.daily-closing-report', $data)->setPaper('a4', 'portrait');

        return $pdf->stream('daily-closing-report.pdf');
    }

    public function todayClosingBalance(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $warehouse = $isAppAdmin ? $request->get('warehouse') : tenant()->id;
        $filters = $this->extractFilters($request);

        $today = Carbon::now('Asia/Dhaka')->toDateString();

        $mergedToday = $this->getTodayDataQuery($today, $warehouse, $filters);

        $balance = 0;
        // 3. Final Query with Search and Pagination
        $data = DB::query()->fromSub($mergedToday, 'combined')
            ->when($search, fn ($q) => $q->whereRaw('LOWER(full_invoice_number) LIKE ?', ['%'.strtolower($search).'%']))
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

        $totals = DB::query()
            ->fromSub($mergedToday, 'ob')
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        return response()->json([
            ...$data->toArray(),
            'grand_total' => ['balance' => ($totals->d ?? 0) - ($totals->c ?? 0)],
        ]);
    }

    public function todayClosingBalanceDownload(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $search = $request->get('search', '');
        $request->get('per_page', 10);
        $warehouse = $isAppAdmin ? $request->get('warehouse') : tenant()->id;
        $filters = $this->extractFilters($request);

        if ($isAppAdmin && ! $warehouse) {
            throw new Exception('Warehouse is required');
        }

        $today = Carbon::now('Asia/Dhaka')->toDateString();

        $mergedToday = $this->getTodayDataQuery($today, $warehouse, $filters);

        $balance = 0;
        // 3. Final Query with Search and Pagination
        $data = DB::query()->fromSub($mergedToday, 'combined')
            ->when($search, fn ($q) => $q->whereRaw('LOWER(full_invoice_number) LIKE ?', ['%'.strtolower($search).'%']))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($item) use (&$balance) {
                $balance += ($item->credit - $item->debit);

                return [
                    'date' => $item->date,
                    'particular' => $item->type,
                    'invoice' => $item->full_invoice_number,
                    'contact_name' => $item->contact_name,
                    'contact_image' => $item->contact_image,
                    'payment_method' => $item->payment_method,
                    'payment_method_logo' => $item->payment_method_logo,
                    'created_by_name' => $item->created_by_name,
                    'seller_name' => $item->seller_name,
                    'seller_image' => $item->seller_image,
                    'tenant_name' => $item->tenant_name,
                    'total' => $item->total,
                    'debit' => $item->debit,
                    'credit' => $item->credit,
                    'balance' => $balance,
                ];
            });

        $totals = DB::query()
            ->fromSub($mergedToday, 'ob')
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        $tenantId = $isAppAdmin ? (is_array($warehouse) ? ($warehouse[0] ?? null) : $warehouse) : tenant()->id;
        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $data = [
            'items' => $data,
            'total_debit' => $totals->c,
            'total_credit' => $totals->d,
            'closing_balance' => ($totals->c ?? 0) - ($totals->d ?? 0),
            'title' => 'Daily Closing Balance Report',
            'date' => $today,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
        ];

        $pdf = Pdf::loadView('pdf.reports.daily-closing-report', $data)->setPaper('a4', 'portrait');

        return $pdf->stream('daily-closing-report.pdf');

        // return response()->json([
        //     ...$data->toArray(),
        //     'grand_total' => ['balance' => ($totals->c ?? 0) - ($totals->d ?? 0)]
        // ]);
    }
}
