<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\StockReportFilter;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Product\Product;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Models\Pos\Transaction\Transaction;
use App\Models\Pos\Expense\Expense;

use App\Filters\Landlord\Expense\ExpenseFilter;

class StatementController extends Controller
{
    /**
     * Generic reusable function for sales, purchases, returns, etc.
     * @throws GeneralException
     */


    //  Transaction
    public function transaction()
    {
        $request = request();

        $date = $request->date ? $this->dateRange($request->date) : null;
        $search = $request->search;
        $party = $request->party;
        $paymentMethod = $request->payment_method;
        $seller = $request->seller;
        $director = $request->director;
        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $warehouse = auth()->user()->isAppAdmin()
            ? $request->warehouse
            : tenant()->id;

        $mergedToday = $this->getTodayDataQuery($date, $warehouse);

        // Grand totals
        $totals = DB::query()
            ->fromSub($mergedToday, 'total_sum')
            ->when($party, fn($q) => $q->where('contact_id', $party))
            ->when($paymentMethod, fn($q) => $q->where('payment_method_id', $paymentMethod))
            ->when($seller, fn($q) => $q->where('seller_id', $seller))
            ->when($director, fn($q) => $q->where('director_id', $director))
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        // Main data query
        $balance = 0;
        $data = DB::query()
            ->fromSub($mergedToday, 'combined')
            ->when($search, function ($q) use ($search) {
                $q->whereRaw("LOWER(full_invoice_number) LIKE ?", ['%' . strtolower($search) . '%']);
            })
            ->when($party, fn($q) => $q->where('contact_id', $party))
            ->when($paymentMethod, fn($q) => $q->where('payment_method_id', $paymentMethod))
            ->when($seller, fn($q) => $q->where('seller_id', $seller))
            ->when($director, fn($q) => $q->where('director_id', $director))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function ($item, $key) use ($perPage, $page, &$balance) {
                $balance += ($item->debit - $item->credit);
                $item->serial = ($perPage * ($page - 1)) + $key + 1;
                $item->balance = $balance;
                return $item;
            });

        return response()->json([
            ...$data->toArray(),
            'grand_total' => [
                'debit' => $totals->total_debit ?? 0,
                'credit' => $totals->total_credit ?? 0,
                'balance' => ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0),
            ]
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function transactionStatementPdf()
    {
        $date = request('date') ? $this->dateRange(\request('date')) : null;

        $warehouse = auth()->user()->isAppAdmin() ? request('warehouse') : tenant()->id;
        $mergedToday = $this->getTodayDataQuery($date, $warehouse);

        $transactions = DB::query()
            ->fromSub($mergedToday, 'combined')
            ->orderBy('created_at', 'desc')
            ->get();

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $warehouse);
        $shopInfo = $warehouse ? DB::table('tenants')->where('id', $warehouse)->first() : null;

        $pdf = PDF::loadView('pdf.transaction-statement', [
            'transactions' => $transactions,
            'shopInfo' => $shopInfo,
            'tenantSettings' => $tenantSettings
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('transaction-statement.pdf');
    }

    private function getTodayDataQuery($dateRange, $warehouse)
    {
        $purchases = DB::table('purchases as p')
            ->leftJoin('contacts as c', 'p.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'p.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'p.created_by', '=', 'u.id')
            ->whereNull('p.deleted_at')
            ->when($dateRange, fn($q) => $q->whereBetween('p.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('p.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('purchase-', p.id) as id"),
                'p.date',
                'p.full_invoice_number',
                DB::raw("'Purchase' as type"),
                'p.grand_total as total',
                DB::raw("0 as debit"),
                DB::raw("0 as credit"),
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                DB::raw("NULL as note"),
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'p.contact_id',
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'p.created_at'
            );

        // 2. Purchase Returns
        $pReturns = DB::table('purchase_returns as pr')
            ->leftJoin('contacts as c', 'pr.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'pr.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'pr.created_by', '=', 'u.id')
            ->whereNull('pr.deleted_at')
            ->when($dateRange, fn($q) => $q->whereBetween('pr.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('pr.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('purchase-return-', pr.id) as id"),
                'pr.date',
                'pr.full_invoice_number',
                DB::raw("'Purchase Return' as type"),
                'pr.grand_total as total',
                DB::raw("0 as debit"),
                DB::raw("0 as credit"),
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                DB::raw("NULL as note"),
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'pr.contact_id',
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'pr.created_at'
            );

        $sales = DB::table('sales as s')
            ->leftJoin('contacts as c', 's.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 's.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 's.created_by', '=', 'u.id')
            ->leftJoin('users as sel', 's.seller_id', '=', 'sel.id')
            ->whereNull('s.deleted_at')
            ->when($dateRange, fn($q) => $q->whereBetween('s.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('s.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('sale-', s.id) as id"),
                's.date',
                's.full_invoice_number',
                DB::raw("'Sale' as type"),
                's.grand_total as total',
                DB::raw("0 as debit"),
                DB::raw("0 as credit"),
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                DB::raw("NULL as note"),
                'u.name as created_by_name',
                'sel.name as seller_name',
                'sel.profile_photo as seller_image',
                's.contact_id',
                DB::raw("NULL as payment_method_id"),
                'sel.id as seller_id',
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                's.created_at'
            );

        // 4. Sale Returns
        $sReturns = DB::table('sale_returns as sr')
            ->leftJoin('contacts as c', 'sr.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'sr.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'sr.created_by', '=', 'u.id')
            ->whereNull('sr.deleted_at')
            ->when($dateRange, fn($q) => $q->whereBetween('sr.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('sr.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('sale-return-', sr.id) as id"),
                'sr.date',
                'sr.full_invoice_number',
                DB::raw("'Sale Return' as type"),
                'sr.grand_total as total',
                DB::raw("0 as debit"),
                DB::raw("0 as credit"),
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                DB::raw("NULL as note"),
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'sr.contact_id',
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'sr.created_at'
            );

        // 5. Ledgers — debit/credit already exist on the table itself
        $ledgers = DB::table('ledgers as l')
            ->leftJoin('contacts as c', 'l.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'l.tenant_id', '=', 't.id')
            ->leftJoin('payment_methods as pm', 'l.payment_method_id', '=', 'pm.id')
            ->when($dateRange, fn($q) => $q->whereBetween('l.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('l.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('ledger-', l.id) as id"),
                'l.date',
                'l.full_invoice_number',
                DB::raw("CASE WHEN IFNULL(l.credit, 0) > 0 THEN 'Payment Received' WHEN IFNULL(l.debit, 0) > 0 THEN 'Payment Sent' ELSE 'Ledger' END as type"),
                DB::raw("IFNULL(l.debit, 0) + IFNULL(l.credit, 0) as total"),
                DB::raw("IFNULL(l.credit, 0) as debit"),
                DB::raw("IFNULL(l.debit, 0) as credit"),
                'c.name as party_name',
                'c.profile_picture as party_image',
                'pm.name as payment_method',
                'pm.logo as payment_method_logo',
                't.name as warehouse_name',
                'l.note',
                DB::raw("NULL as created_by_name"),
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'l.contact_id',
                'l.payment_method_id',
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'l.created_at'
            );

        // 6. Expenses, Withdraws, Investments
        $expenses = DB::table('expenses as e')
            ->leftJoin('tenants as t', 'e.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'e.created_by', '=', 'u.id')
            ->leftJoin('payment_methods as pm', 'e.payment_method_id', '=', 'pm.id')
            ->when($dateRange, fn($q) => $q->whereBetween('e.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('e.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('expense-', e.id) as id"),
                'e.date',
                DB::raw("CONCAT('EXP-', e.id) as full_invoice_number"),
                DB::raw("'Expense' as type"),
                'e.amount as total',
                DB::raw('0 as debit'),
                'e.amount as credit',
                DB::raw("NULL as party_name"),
                DB::raw("NULL as party_image"),
                'pm.name as payment_method',
                'pm.logo as payment_method_logo',
                't.name as warehouse_name',
                'e.note',
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                DB::raw("NULL as contact_id"),
                'e.payment_method_id',
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'e.created_at'
            );

        $withdraws = DB::table('incomes as i')
            ->leftJoin('contacts as c', 'i.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'i.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'i.created_by', '=', 'u.id')
            ->leftJoin('users as dir', 'i.user_id', '=', 'dir.id')
            ->when($dateRange, fn($q) => $q->whereBetween('i.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('i.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('withdraw-', i.id) as id"),
                'i.date',
                DB::raw("CONCAT('WTH-', i.id) as full_invoice_number"),
                DB::raw("'Withdraw' as type"),
                'i.amount as total',
                DB::raw('0 as debit'),
                'i.amount as credit',
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                'i.note',
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'i.contact_id',
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                'dir.name as director_name',
                'dir.profile_photo as director_image',
                'i.user_id as director_id',
                'i.created_at'
            );

        $investments = DB::table('investments as inv')
            ->leftJoin('contacts as c', 'inv.contact_id', '=', 'c.id')
            ->leftJoin('tenants as t', 'inv.tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'inv.created_by', '=', 'u.id')
            ->leftJoin('users as dir', 'inv.user_id', '=', 'dir.id')
            ->when($dateRange, fn($q) => $q->whereBetween('inv.date', $dateRange))
            ->when($warehouse, fn($q) => $q->where('inv.tenant_id', $warehouse))
            ->select(
                DB::raw("CONCAT('deposit-', inv.id) as id"),
                'inv.date',
                DB::raw("CONCAT('INV-', inv.id) as full_invoice_number"),
                DB::raw("'Deposit' as type"),
                'inv.amount as total',
                'inv.amount as debit',
                DB::raw('0 as credit'),
                'c.name as party_name',
                'c.profile_picture as party_image',
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                'inv.note',
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                'inv.contact_id',
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                'dir.name as director_name',
                'dir.profile_photo as director_image',
                'inv.user_id as director_id',
                'inv.created_at'
            );

        // 8. Cash Transfer OUT — money leaves this warehouse (credit)
        $cashTransferOut = DB::table('cash_transfers as ct')
            ->leftJoin('tenants as t', 'ct.from_tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'ct.created_by', '=', 'u.id')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->when($dateRange, fn($q) => $q->whereBetween('transfer_date', $dateRange))
            ->when(
                $warehouse,
                fn($q) => $q->where('ct.from_tenant_id', $warehouse),
                fn($q) => $q->whereRaw('0=1')
            )
            ->select(
                DB::raw("CONCAT('cash-transfer-out-', ct.id) as id"),
                'ct.transfer_date as date',
                'ct.reference_no as full_invoice_number',
                DB::raw("'Cash Transfer Out' as type"),
                'ct.amount as total',
                DB::raw('0 as debit'),
                'ct.amount as credit',
                DB::raw("NULL as party_name"),
                DB::raw("NULL as party_image"),
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                'ct.note',
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                DB::raw("NULL as contact_id"),
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'ct.created_at'
            );

        // 9. Cash Transfer IN — money arrives at this warehouse (debit)
        $cashTransferIn = DB::table('cash_transfers as ct')
            ->leftJoin('tenants as t', 'ct.to_tenant_id', '=', 't.id')
            ->leftJoin('users as u', 'ct.created_by', '=', 'u.id')
            ->whereNull('ct.deleted_at')
            ->where('ct.status', 'approved')
            ->when($dateRange, fn($q) => $q->whereBetween('transfer_date', $dateRange))
            ->when(
                $warehouse,
                fn($q) => $q->where('ct.to_tenant_id', $warehouse),
                fn($q) => $q->whereRaw('0=1')
            )
            ->select(
                DB::raw("CONCAT('cash-transfer-in-', ct.id) as id"),
                'ct.transfer_date as date',
                'ct.reference_no as full_invoice_number',
                DB::raw("'Cash Transfer In' as type"),
                'ct.amount as total',
                'ct.amount as debit',
                DB::raw('0 as credit'),
                DB::raw("NULL as party_name"),
                DB::raw("NULL as party_image"),
                DB::raw("NULL as payment_method"),
                DB::raw("NULL as payment_method_logo"),
                't.name as warehouse_name',
                'ct.note',
                'u.name as created_by_name',
                DB::raw("NULL as seller_name"),
                DB::raw("NULL as seller_image"),
                DB::raw("NULL as contact_id"),
                DB::raw("NULL as payment_method_id"),
                DB::raw("NULL as seller_id"),
                DB::raw("NULL as director_name"),
                DB::raw("NULL as director_image"),
                DB::raw("NULL as director_id"),
                'ct.created_at'
            );

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

    /**
     * @throws GeneralException
     */
    public function dateRange($range = null)
    {
        if (!$range) {
            return null;
        }

        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (!is_array($range) || count($range) < 2) {
            return null;
        }

        try {
            return [
                Carbon::parse($range[0])->startOfDay(),
                Carbon::parse($range[1])->endOfDay()
            ];
        } catch (\Exception $e) {
            throw new GeneralException('Invalid your date range');
        }
    }

    private function getTransactionType($transaction): string
    {
        return match ($transaction->model_type) {
            'App\Models\Pos\Purchase\Purchase', 'App\Models\Pos\SaleReturn\SaleReturn' => 'Outgoing',
            'App\Models\Pos\Sale\Sale', 'App\Models\Pos\PurchaseReturn\PurchaseReturn' => 'Incoming',
            default => 'Unknown',
        };
    }

    private function getTransactionModel($transaction): string
    {
        return match ($transaction->model_type) {
            'App\Models\Pos\Purchase\Purchase' => 'Purchase',
            'App\Models\Pos\Sale\Sale' => 'Sale',
            'App\Models\Pos\SaleReturn\SaleReturn' => 'Sale Return',
            'App\Models\Pos\PurchaseReturn\PurchaseReturn' => 'Purchase Return',
            'App\Models\Pos\Expense\Expense' => 'Expense',
            default => 'Unknown',
        };
    }

    // 💸 Expenses
    public function expenseStatement()
    {
        $dateRange = request('date');
        $query = Expense::query()
            ->filters(new ExpenseFilter())
            ->when(empty($dateRange), fn($q) => $q->whereDate('date', Carbon::today()))
            ->with(['category:id,name', 'payments.paymentMethod:id,name'])
            ->orderByDesc('id');

        $grandTotal = (clone $query)->sum('amount');


        $paidAmount = Transaction::query()
            ->join(DB::raw("({$query->toSql()}) as t"), 'transactions.model_id', '=', 't.id')
            ->addBinding($query->getBindings(), 'join')
            ->where('transactions.model_type', Expense::class)
            ->sum('transactions.amount');

        $expenses = $query->paginate(request('per_page', 15))
            ->through(fn($e, $i) => [
                'index' => $i + 1,
                ...$e->toArray(),
                'paid_amount' => $e->getTransactionAmountAttribute(),
                'payments' => $e->payments->first() ?? [],
            ]);

        return [
            ...$expenses->toArray(),
            'grand_total' => [
                'amount' => floor($grandTotal),
                'paid_amount' => floor($paidAmount),
            ],
        ];
    }

    public function index(Request $request)
    {
        $perPage   = $request->input('per_page', 10);
        $brand     = $request->input('brand');
        $category  = $request->input('category');
        $wareHouse = $request->input('warehouse');
        $product   = $request->input('product');
        $supplier  = $request->input('supplier');

        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId   = $isAppAdmin ? $wareHouse : tenant()->id;

        // Supplier is matched per product+variant batch, not just per
        // product — a product's variants can come from different suppliers,
        // and matching only on product_id let a variant purchased solely
        // from a different supplier leak into the filtered results (and
        // show that other supplier's name on the row).
        $supplierIds = $supplier ? explode(',', $supplier) : null;

        $brandIds = $brand ? explode(',', $brand) : null;

        // Filtering by a parent category should also pull in its children's
        // and grandchildren's stock, not just products directly on that exact
        // category — so expand each selected id into itself + every descendant
        // (mirroring StockReportController's category handling), and reuse the
        // same expanded set for the grand total below so it stays consistent
        // with the row list (which applies this via StockReportFilter).
        $categoryIds = $category ? Category::query()
            ->whereIn('id', explode(',', $category))
            ->get()
            ->flatMap(fn (Category $cat) => $cat->getAllDescendantIds())
            ->unique()
            ->values()
            ->all() : null;

        $productIds = $product ? explode(',', $product) : null;

        // ── 1. Stock grouped by product + variant ──────────────────────────────
        // ✅ FIX: tenant_product_serials DISTINCT serial count
        $stockQuery = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->select([
                'ps.product_id',
                'ps.product_variant_id',
                DB::raw('COUNT(DISTINCT ps.serial_number) as qty'),
            ])
            ->groupBy('ps.product_id', 'ps.product_variant_id');

        // ── 2. Join products ───────────────────────────────────────────────────
        // ✅ FIX: filters() Product model এ — warehouse filter products query apply 
        $productsQuery = Product::query()
            ->filters(new StockReportFilter())
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.product_image',
                'products.brand_id',
                'products.category_id',
                'stock.product_variant_id',
                'stock.qty'
            )
            ->joinSub($stockQuery, 'stock', fn($join) => $join->on('products.id', '=', 'stock.product_id'))
            ->when($supplierIds, function ($q) use ($supplierIds, $tenantId) {
                // Matching "this variant was ever purchased from the
                // supplier" (product+variant only) let a variant currently
                // in stock purely from a DIFFERENT supplier's batch pass the
                // filter just because some unrelated/older batch of the same
                // variant once came from the selected supplier. The check
                // has to land on a serial that is actually still held in
                // stock right now, not just any historical purchase line.
                $q->whereExists(function ($sub) use ($supplierIds, $tenantId) {
                    $sub->selectRaw('1')
                        ->from('purchase_items as spi')
                        ->join('purchases as spur', 'spur.id', '=', 'spi.purchase_id')
                        ->join('product_serials as sps', function ($join) {
                            $join->on('sps.product_id', '=', 'spi.product_id')
                                ->on('sps.product_variant_id', '=', 'spi.product_variant_id')
                                ->on('sps.serial_number', '=', 'spi.serial_number');
                        })
                        ->join('tenant_product_serials as stps', function ($join) use ($tenantId) {
                            $join->on('stps.product_serial_id', '=', 'sps.id')
                                ->when($tenantId, fn($j) => $j->where('stps.tenant_id', $tenantId));
                        })
                        ->whereColumn('spi.product_id', 'products.id')
                        ->whereColumn('spi.product_variant_id', 'stock.product_variant_id')
                        ->whereIn('stps.status', ['purchase', 'sale_return'])
                        ->whereNull('spi.deleted_at')
                        ->whereNull('spur.deleted_at')
                        ->whereIn('spur.contact_id', $supplierIds);
                });
            })
            ->where('stock.qty', '>', 0)
            ->orderBy('products.id');

        // Full matching set (all pages) — needed so the "Quantity" grand
        // total can sum the same per-variant batch quantity shown on every
        // row, not just the current page's.
        $allMatchingVariantIds = (clone $productsQuery)
            ->pluck('stock.product_variant_id')
            ->unique()
            ->filter()
            ->values();

        $paginated  = $productsQuery->paginate($perPage);
        $startIndex = ($paginated->currentPage() - 1) * $paginated->perPage();

        // ── 3. Variant IDs from current page ───────────────────────────────────
        $variantIds = $paginated->getCollection()
            ->pluck('product_variant_id')
            ->unique()
            ->filter()
            ->values();

        if ($variantIds->isEmpty()) {
            return $this->emptyResponse($paginated);
        }

        // ── 4. Variant basic info ──────────────────────────────────────────────
        $variants = DB::table('product_variants')
            ->whereIn('id', $variantIds)
            ->whereNull('deleted_at')
            ->select('id', 'product_id', 'sku')
            ->get()
            ->keyBy('id');

        // ── 5. Variant attribute values ────────────────────────────────────────
        $variantAttributes = DB::table('variant_attribute_values as pvav')
            ->join('attribute_values as av', 'pvav.attribute_value_id', '=', 'av.id')
            ->whereIn('pvav.product_variant_id', $variantIds)
            ->select('pvav.product_variant_id', 'av.value')
            ->get()
            ->groupBy('product_variant_id');

        // ── 6. Serial numbers (current stock only) ────────────────────────────
        // ✅ FIX: groupBy duplicate serial
        $serials = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->whereIn('ps.product_variant_id', $variantIds)
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->select('ps.product_variant_id', 'ps.serial_number')
            ->groupBy('ps.product_variant_id', 'ps.serial_number')
            ->get()
            ->groupBy('product_variant_id');

        // ── 6b. Last restock info (date/supplier/invoice/warehouse/qty) ───────
        // Net stock is aggregated across every purchase batch for a variant,
        // so there's no single purchase to point at — show the most recent
        // one instead, mirroring StockReportController's "latest_id" pattern.
        //
        // Traced via the SERIAL NUMBERS this tenant currently physically
        // holds (tenant_product_serials), not via purchase.tenant_id — a
        // serial can be TRANSFERRED to a different warehouse after purchase,
        // so a warehouse that received stock only via transfer would have no
        // purchase_items of its own for that variant, and filtering by
        // purchase.tenant_id would wrongly show no restock info at all even
        // though the stock is clearly sitting there.
        $currentSerialsForRestock = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->whereIn('ps.product_variant_id', $variantIds)
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->select('ps.product_variant_id', 'ps.serial_number')
            ->distinct();

        $latestPurchaseItemIds = DB::table('purchase_items as pi')
            ->joinSub($currentSerialsForRestock, 'cs', function ($join) {
                $join->on('cs.product_variant_id', '=', 'pi.product_variant_id')
                    ->on('cs.serial_number', '=', 'pi.serial_number');
            })
            ->when($supplierIds, function ($q) use ($supplierIds) {
                // Without this, the "last restock" shown would be the
                // globally latest purchase for the variant even when a
                // supplier filter is active — surfacing a different
                // supplier's name on a row filtered to this one.
                $q->join('purchases as rpur', 'rpur.id', '=', 'pi.purchase_id')
                    ->whereIn('rpur.contact_id', $supplierIds);
            })
            ->whereNull('pi.deleted_at')
            ->select('pi.product_variant_id', DB::raw('MAX(pi.id) as latest_id'))
            ->groupBy('pi.product_variant_id');

        $latestPurchaseInfo = DB::table('purchase_items as pi')
            ->joinSub($latestPurchaseItemIds, 'lpi', fn($join) => $join->on('lpi.latest_id', '=', 'pi.id'))
            ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'pur.contact_id')
            ->leftJoin('tenants as t', 't.id', '=', 'pur.tenant_id')
            ->select(
                'pi.product_variant_id',
                'pi.purchase_id',
                'pur.date as purchase_date',
                'pur.full_invoice_number',
                'c.name as supplier_name',
                'c.profile_picture as supplier_image',
                't.name as warehouse_name'
            )
            ->get()
            ->keyBy('product_variant_id');

        // "Quantity" = size of that same restock batch (how many serials
        // were purchased together on that invoice for this variant) — a
        // fixed historical fact about the batch itself, so it's counted
        // company-wide rather than re-filtered by tenant.
        //
        // Purchase invoices are multi-line, so the pool of restock purchase
        // IDs collected across the whole page mixes unrelated variants onto
        // the same invoice. Keying strictly by product_variant_id (without
        // purchase_id) let a variant's OWN restock qty come from a
        // DIFFERENT variant's invoice that merely happened to also line-item
        // this variant on some unrelated, older purchase — so this must be
        // keyed by the (variant, purchase) pair actually chosen above, not
        // variant alone.
        $restockPurchaseIds = $latestPurchaseInfo->pluck('purchase_id')->unique()->filter()->values();

        $batchQuantities = $restockPurchaseIds->isEmpty() ? collect() : DB::table('purchase_items')
            ->whereNull('deleted_at')
            ->whereIn('purchase_id', $restockPurchaseIds)
            ->whereIn('product_variant_id', $variantIds)
            ->select('product_variant_id', 'purchase_id', DB::raw('COUNT(DISTINCT serial_number) as qty'))
            ->groupBy('product_variant_id', 'purchase_id')
            ->get()
            ->keyBy(fn($row) => "{$row->product_variant_id}-{$row->purchase_id}");

        // ── 8. Grand total ────────────────────────────────────────────────────
        // ✅ FIX: total_in = purchase + sale_return DISTINCT 
        $grandStats = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->join('product_variants as pv', 'pv.id', '=', 'ps.product_variant_id')
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->when($brandIds,    fn($q) => $q->whereIn('p.brand_id', $brandIds))
            ->when($categoryIds, fn($q) => $q->whereIn('p.category_id', $categoryIds))
            ->when($productIds,  fn($q) => $q->whereIn('ps.product_id', $productIds))
            ->when($supplierIds, function ($q) use ($supplierIds) {
                // Grand total is computed per currently-held serial (this
                // query's outer FROM is tenant_product_serials/product_serials),
                // so the match can go straight to that exact serial's own
                // purchase record instead of "any batch of this variant" —
                // more precise than the product+variant check needed above
                // where no specific serial is in scope yet.
                $q->whereExists(function ($sub) use ($supplierIds) {
                    $sub->selectRaw('1')
                        ->from('purchase_items as spi')
                        ->join('purchases as spur', 'spur.id', '=', 'spi.purchase_id')
                        ->whereColumn('spi.product_id', 'ps.product_id')
                        ->whereColumn('spi.product_variant_id', 'ps.product_variant_id')
                        ->whereColumn('spi.serial_number', 'ps.serial_number')
                        ->whereNull('spi.deleted_at')
                        ->whereNull('spur.deleted_at')
                        ->whereIn('spur.contact_id', $supplierIds);
                });
            })
            ->select([
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status IN ('purchase', 'sale_return') THEN ps.serial_number END) as total_in"),
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status IN ('sale', 'purchase_return') THEN ps.serial_number END) as total_out"),
            ])
            ->first();

        $grandTotalIn  = $grandStats->total_in  ?? 0;
        $grandTotalOut = $grandStats->total_out ?? 0;

        // "Quantity" grand total = sum of each matching variant's latest
        // restock batch size (same metric shown per row — see 6b above),
        // traced via currently-held serials so transferred-in stock is
        // attributed correctly instead of being invisible to a warehouse
        // that never purchased it directly.
        $grandPurchased = 0;

        if ($allMatchingVariantIds->isNotEmpty()) {
            $grandCurrentSerials = DB::table('tenant_product_serials as tps')
                ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
                ->whereIn('ps.product_variant_id', $allMatchingVariantIds)
                ->whereIn('tps.status', ['purchase', 'sale_return'])
                ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
                ->select('ps.product_variant_id', 'ps.serial_number')
                ->distinct();

            $grandLatestPurchaseItemIds = DB::table('purchase_items as pi')
                ->joinSub($grandCurrentSerials, 'cs', function ($join) {
                    $join->on('cs.product_variant_id', '=', 'pi.product_variant_id')
                        ->on('cs.serial_number', '=', 'pi.serial_number');
                })
                ->whereNull('pi.deleted_at')
                ->select('pi.product_variant_id', DB::raw('MAX(pi.id) as latest_id'))
                ->groupBy('pi.product_variant_id');

            // Each variant's own (variant, purchase) pair is kept explicit
            // rather than flattening to a plain purchase-id list — the same
            // multi-line-invoice leak as the per-row batch quantities above
            // would otherwise let one variant's restock qty be sourced from
            // a different variant's unrelated line item on a shared invoice.
            $grandRestockPairs = DB::table('purchase_items as pi')
                ->joinSub($grandLatestPurchaseItemIds, 'lpi', fn($join) => $join->on('lpi.latest_id', '=', 'pi.id'))
                ->select('pi.product_variant_id', 'pi.purchase_id')
                ->get();

            if ($grandRestockPairs->isNotEmpty()) {
                $grandPurchaseIds = $grandRestockPairs->pluck('purchase_id')->unique()->values();

                $grandBatchQuantities = DB::table('purchase_items')
                    ->whereNull('deleted_at')
                    ->whereIn('purchase_id', $grandPurchaseIds)
                    ->whereIn('product_variant_id', $allMatchingVariantIds)
                    ->select('product_variant_id', 'purchase_id', DB::raw('COUNT(DISTINCT serial_number) as qty'))
                    ->groupBy('product_variant_id', 'purchase_id')
                    ->get()
                    ->keyBy(fn($row) => "{$row->product_variant_id}-{$row->purchase_id}");

                $grandPurchased = $grandRestockPairs->sum(
                    fn($pair) => $grandBatchQuantities->get("{$pair->product_variant_id}-{$pair->purchase_id}")->qty ?? 0
                );
            }
        }

        // ── 9. Final data map ──────────────────────────────────────────────────
        $data = $paginated->getCollection()->values()->map(function ($row, $index) use (
            $variants,
            $serials,
            $variantAttributes,
            $batchQuantities,
            $latestPurchaseInfo,
            $startIndex
        ) {
            $vId = $row->product_variant_id;

            if (! isset($variants[$vId])) {
                return null;
            }

            $lastPurchase = $latestPurchaseInfo->get($vId);
            $totalPurchase = $lastPurchase
                ? (int) ($batchQuantities->get("{$vId}-{$lastPurchase->purchase_id}")->qty ?? 0)
                : 0;

            $variantSerials = $serials->get($vId, collect());

            return [
                'sl'            => $startIndex + $index + 1,
                'product_name'  => $row->product_name,
                'product_image' => $row->product_image,
                'brand_name'    => optional($row->brand)->name,
                'category_name' => optional($row->category)->name,
                'variant_sku'   => $variants[$vId]->sku ?? null,

                'attribute_values' => $variantAttributes
                    ->get($vId, collect())
                    ->pluck('value')
                    ->implode('/'),

                'purchase_date'       => $lastPurchase->purchase_date ?? null,
                'full_invoice_number' => $lastPurchase->full_invoice_number ?? null,
                'supplier_name'       => $lastPurchase->supplier_name ?? '-',
                'supplier_image'      => $lastPurchase->supplier_image ?? null,
                'warehouse_name'      => $lastPurchase->warehouse_name ?? '-',

                'serial_numbers' => $variantSerials
                    ->pluck('serial_number')
                    ->unique()
                    ->values()
                    ->toArray(),

                'serial_count'          => $variantSerials->pluck('serial_number')->unique()->count(),

                'purchased_qty' => $totalPurchase,
                'net_stock' => $variantSerials->pluck('serial_number')->unique()->count(),
            ];
        })->filter()->values();

        // ── 10. Response ───────────────────────────────────────────────────────
        return response()->json([
            'grand_total' => [
                'total_in'  => (int) $grandTotalIn,
                'total_out' => (int) $grandTotalOut,
                'purchased_qty' => (int) $grandPurchased,
                'net_stock' => (int) $grandTotalIn,
            ],
            'current_page'   => $paginated->currentPage(),
            'data'           => $data,
            'first_page_url' => $paginated->url(1),
            'from'           => $paginated->firstItem(),
            'last_page'      => $paginated->lastPage(),
            'last_page_url'  => $paginated->url($paginated->lastPage()),
            'next_page_url'  => $paginated->nextPageUrl(),
            'path'           => $paginated->path(),
            'per_page'       => $paginated->perPage(),
            'prev_page_url'  => $paginated->previousPageUrl(),
            'to'             => $paginated->lastItem(),
            'total'          => $paginated->total(),
        ]);
    }

    // ── Helper: empty response ─────────────────────────────────────────────────
    private function emptyResponse($paginated): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'grand_total'    => ['total_in' => 0, 'total_out' => 0, 'purchased_qty' => 0, 'net_stock' => 0],
            'current_page'   => $paginated->currentPage(),
            'data'           => [],
            'first_page_url' => $paginated->url(1),
            'from'           => null,
            'last_page'      => $paginated->lastPage(),
            'last_page_url'  => $paginated->url($paginated->lastPage()),
            'next_page_url'  => $paginated->nextPageUrl(),
            'path'           => $paginated->path(),
            'per_page'       => $paginated->perPage(),
            'prev_page_url'  => $paginated->previousPageUrl(),
            'to'             => null,
            'total'          => $paginated->total(),
        ]);
    }


    // 🏷️ Stock statement
    // public function index(Request $request)
    // {
    //     $dateRange = $request->input('date');
    //     [$fromDate, $toDate] = $this->resolveDateRange($dateRange);

    //     $searchQuery = $request->input('search');
    //     $perPage = $request->input('per_page', 15);

    //     // Base query
    //     $query = Stock::query()
    //         ->select([
    //             'stocks.product_variant_id',
    //             DB::raw('MAX(products.product_name) as product'),
    //             DB::raw('MAX(product_variants.sku) as sku'),
    //             DB::raw('MAX(stocks.quantity) as opening_stock'),

    //             // Purchases
    //             DB::raw("(SELECT COALESCE(SUM(pi.quantity),0)
    //                   FROM purchase_items pi
    //                   JOIN purchases p ON p.id = pi.purchase_id
    //                   WHERE pi.product_variant_id = stocks.product_variant_id
    //                   AND p.date BETWEEN '$fromDate' AND '$toDate') as purchase"),

    //             // Purchase Returns
    //             DB::raw("(SELECT COALESCE(SUM(pri.quantity),0)
    //                   FROM purchase_return_items pri
    //                   JOIN purchase_returns pr ON pr.id = pri.purchase_return_id
    //                   WHERE pri.product_variant_id = stocks.product_variant_id
    //                   AND pr.date BETWEEN '$fromDate' AND '$toDate') as purchase_return"),

    //             // Sales
    //             DB::raw("(SELECT COALESCE(SUM(si.quantity),0)
    //                   FROM sale_items si
    //                   JOIN sales s ON s.id = si.sale_id
    //                   WHERE si.product_variant_id = stocks.product_variant_id
    //                   AND s.date BETWEEN '$fromDate' AND '$toDate') as sale"),

    //             // Sale Returns
    //             DB::raw("(SELECT COALESCE(SUM(sri.quantity),0)
    //                   FROM sale_return_items sri
    //                   JOIN sale_returns sr ON sr.id = sri.sale_return_id
    //                   WHERE sri.product_variant_id = stocks.product_variant_id
    //                   AND sr.date BETWEEN '$fromDate' AND '$toDate') as sale_return"),
    //         ])
    //         ->when($searchQuery, fn($q, $sku) => $q->where('product_variants.sku', 'like', "%{$sku}%"))
    //         ->leftJoin('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
    //         ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
    //         ->groupBy('stocks.product_variant_id')
    //         ->orderByDesc('stocks.product_variant_id');

    //     // Clone query for grand totals (without pagination)
    //     $grandTotals = (clone $query)->get()->reduce(function ($totals, $row) use ($fromDate, $toDate) {
    //         $closing_stock = $row->opening_stock + $row->purchase - $row->purchase_return - $row->sale + $row->sale_return;

    //         $totals['opening_stock'] = ($totals['opening_stock'] ?? 0) + $row->opening_stock;
    //         $totals['purchase'] = ($totals['purchase'] ?? 0) + $row->purchase;
    //         $totals['purchase_return'] = ($totals['purchase_return'] ?? 0) + $row->purchase_return;
    //         $totals['sale'] = ($totals['sale'] ?? 0) + $row->sale;
    //         $totals['sale_return'] = ($totals['sale_return'] ?? 0) + $row->sale_return;
    //         $totals['closing_stock'] = ($totals['closing_stock'] ?? 0) + $closing_stock;

    //         return $totals;
    //     }, []);

    //     // Paginate and format data
    //     $stocks = $query->paginate($perPage)->through(fn($row, $i) => [
    //         'index' => $i + 1,
    //         'product_variant_id' => (int) $row->product_variant_id,
    //         'product' => $row->product,
    //         'sku' => $row->sku,
    //         'opening_stock' => (int) $row->opening_stock,
    //         'purchase' => (int) $row->purchase,
    //         'purchase_return' => (int) $row->purchase_return,
    //         'sale' => (int) $row->sale,
    //         'sale_return' => (int) $row->sale_return,
    //         'closing_stock' => (int) $row->opening_stock + (int) $row->purchase - (int) $row->purchase_return - (int) $row->sale + (int) $row->sale_return,
    //     ]);

    //     return [
    //         'grand_total' => $grandTotals,
    //         ...$stocks->toArray(),
    //     ];
    // }


    // public function index(Request $request)
    // {
    //     $dateRange = $request->input('date');
    //     [$fromDate, $toDate] = $this->resolveDateRange($dateRange);

    //     $searchQuery = $request->input('search');
    //     $perPage = $request->input('per_page', 15);

    //     // Base query
    //     $query = Stock::query()
    //         ->select([
    //             'stocks.product_variant_id',
    //             DB::raw('MAX(products.product_name) as product'),
    //             DB::raw('MAX(product_variants.sku) as sku'),
    //             DB::raw('MAX(stocks.quantity) as opening_stock'),

    //             // Purchases
    //             DB::raw("(SELECT COALESCE(SUM(pi.quantity),0)
    //               FROM purchase_items pi
    //               JOIN purchases p ON p.id = pi.purchase_id
    //               WHERE pi.product_variant_id = stocks.product_variant_id
    //               AND p.date BETWEEN '$fromDate' AND '$toDate') as purchase"),

    //             // Purchase Returns
    //             DB::raw("(SELECT COALESCE(SUM(pri.quantity),0)
    //               FROM purchase_return_items pri
    //               JOIN purchase_returns pr ON pr.id = pri.purchase_return_id
    //               WHERE pri.product_variant_id = stocks.product_variant_id
    //               AND pr.date BETWEEN '$fromDate' AND '$toDate') as purchase_return"),

    //             // Sales
    //             DB::raw("(SELECT COALESCE(SUM(si.quantity),0)
    //               FROM sale_items si
    //               JOIN sales s ON s.id = si.sale_id
    //               WHERE si.product_variant_id = stocks.product_variant_id
    //               AND s.date BETWEEN '$fromDate' AND '$toDate') as sale"),

    //             // Sale Returns
    //             DB::raw("(SELECT COALESCE(SUM(sri.quantity),0)
    //               FROM sale_return_items sri
    //               JOIN sale_returns sr ON sr.id = sri.sale_return_id
    //               WHERE sri.product_variant_id = stocks.product_variant_id
    //               AND sr.date BETWEEN '$fromDate' AND '$toDate') as sale_return"),
    //         ])
    //         ->when($searchQuery, fn($q, $sku) => $q->where('product_variants.sku', 'like', "%{$sku}%"))
    //         ->leftJoin('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
    //         ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
    //         ->groupBy('stocks.product_variant_id')
    //         ->orderByDesc('stocks.product_variant_id');

    //     // Clone query for grand totals (without pagination)
    //     $grandTotals = (clone $query)->get()->reduce(function ($totals, $row) {
    //         $opening_stock = (int)$row->opening_stock;
    //         $purchase = (int)$row->purchase;
    //         $purchase_return = (int)$row->purchase_return;
    //         $sale = (int)$row->sale;
    //         $sale_return = (int)$row->sale_return;

    //         $closing_stock = $opening_stock + $purchase - $purchase_return - $sale + $sale_return;

    //         $totals['opening_stock'] = ($totals['opening_stock'] ?? 0) + $opening_stock;
    //         $totals['purchase'] = ($totals['purchase'] ?? 0) + $purchase;
    //         $totals['purchase_return'] = ($totals['purchase_return'] ?? 0) + $purchase_return;
    //         $totals['sale'] = ($totals['sale'] ?? 0) + $sale;
    //         $totals['sale_return'] = ($totals['sale_return'] ?? 0) + $sale_return;
    //         $totals['closing_stock'] = ($totals['closing_stock'] ?? 0) + $closing_stock;

    //         return $totals;
    //     }, []);

    //     // Paginate
    //     $paginated = $query->paginate($perPage);
    //     $counter = 0;

    //     $groupedData = $paginated->getCollection()->groupBy('product')->map(function ($variants, $productName) use (&$counter) {
    //         if ($variants->count() === 1) {
    //             $row = $variants->first();
    //             $counter++;
    //             return [
    //                 'index' => $counter,
    //                 'product_variant_id' => (int)$row->product_variant_id,
    //                 'product' => $row->product,
    //                 'sku' => $row->sku,
    //                 'opening_stock' => (int)$row->opening_stock,
    //                 'purchase' => (int)$row->purchase,
    //                 'purchase_return' => (int)$row->purchase_return,
    //                 'sale' => (int)$row->sale,
    //                 'sale_return' => (int)$row->sale_return,
    //                 'closing_stock' => (int)$row->opening_stock + (int)$row->purchase - (int)$row->purchase_return - (int)$row->sale + (int)$row->sale_return,
    //             ];
    //         } else {
    //             $counter++;
    //             $productVariants = $variants->map(function ($row) {
    //                 return [
    //                     'product_variant_id' => (int)$row->product_variant_id,
    //                     'sku' => $row->sku,
    //                     'opening_stock' => (int)$row->opening_stock,
    //                     'purchase' => (int)$row->purchase,
    //                     'purchase_return' => (int)$row->purchase_return,
    //                     'sale' => (int)$row->sale,
    //                     'sale_return' => (int)$row->sale_return,
    //                     'closing_stock' => (int)$row->opening_stock + (int)$row->purchase - (int)$row->purchase_return - (int)$row->sale + (int)$row->sale_return,
    //                 ];
    //             });

    //             return [
    //                 'index' => $counter,
    //                 'product' => $productName,
    //                 'products' => $productVariants,
    //                 'opening_stock' => $productVariants->sum('opening_stock'),
    //                 'purchase' => $productVariants->sum('purchase'),
    //                 'purchase_return' => $productVariants->sum('purchase_return'),
    //                 'sale' => $productVariants->sum('sale'),
    //                 'sale_return' => $productVariants->sum('sale_return'),
    //                 'closing_stock' => $productVariants->sum('closing_stock'),
    //             ];
    //         }
    //     })->values();


    //     // Replace paginated collection with grouped data
    //     $paginated->setCollection($groupedData);

    //     return [
    //         'grand_total' => $grandTotals,
    //         ...$paginated->toArray(),
    //     ];
    // }


    // public function index(Request $request)
    // {
    //     $searchQuery = $request->input('search');
    //     $perPage = $request->input('per_page', 15);
    //     $dateRange = $request->input('date');

    //     if (!$dateRange) {
    //         $fromDate = Carbon::today()->startOfDay();
    //         $toDate = Carbon::today()->endOfDay();
    //     } else {
    //         [$fromDate, $toDate] = $this->resolveDateRange($dateRange);
    //         $fromDate = Carbon::parse($fromDate)->startOfDay();
    //         $toDate = Carbon::parse($toDate)->endOfDay();
    //     }

    //     // Base query for variants
    //     $query = DB::table('stocks')
    //         ->select([
    //             'stocks.product_variant_id',
    //             'products.product_name as product',
    //             'product_variants.sku',
    //         ])
    //         ->leftJoin('product_variants', 'stocks.product_variant_id', '=', 'product_variants.id')
    //         ->leftJoin('products', 'product_variants.product_id', '=', 'products.id')
    //         ->when($searchQuery, fn($q, $sku) => $q->where('product_variants.sku', 'like', "%{$sku}%"))
    //         ->groupBy('stocks.product_variant_id', 'products.product_name', 'product_variants.sku')
    //         ->orderByDesc('stocks.product_variant_id');

    //     $allVariants = $query->get();

    //     $allSerials = DB::table('product_serials')
    //         ->select('product_variant_id', 'serial_number')
    //         ->whereIn('status', ['purchase', 'sale_return'])
    //         ->when($fromDate && $toDate, fn($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
    //         ->get()
    //         ->groupBy('product_variant_id');

    //     $grandTotalQty = $allVariants->reduce(function ($carry, $row) use ($allSerials) {
    //         $serialNumbers = collect($allSerials->get((int)$row->product_variant_id, []))
    //             ->pluck('serial_number')
    //             ->toArray();
    //         return $carry + count($serialNumbers);
    //     }, 0);

    //     $paginated = $query->paginate($perPage);
    //     $counter = 0;

    //     // Map paginated collection
    //     $groupedData = $paginated->getCollection()
    //         ->groupBy('product')
    //         ->map(function ($variants, $productName) use (&$counter, $allSerials) {
    //             $counter++;

    //             $variantsData = $variants->map(function ($row) use ($allSerials) {
    //                 $serialNumbers = collect($allSerials->get((int)$row->product_variant_id, []))
    //                     ->pluck('serial_number')
    //                     ->toArray();
    //                 return [
    //                     'product_variant_id' => $row->product_variant_id,
    //                     'sku' => $row->sku,
    //                     'qty' => count($serialNumbers),
    //                     'serial_numbers' => $serialNumbers,
    //                 ];
    //             });

    //             $totalQty = $variantsData->sum('qty');

    //             if ($totalQty === 0) {
    //                 return null;
    //             }

    //             return [
    //                 'index' => $counter,
    //                 'product' => $productName,
    //                 'products' => $variantsData->values(),
    //                 'qty' => $totalQty,
    //                 'serial_numbers' => array_merge(...$variantsData->pluck('serial_numbers')->toArray()),
    //             ];
    //         })
    //         ->filter()
    //         ->values();

    //     $paginated->setCollection($groupedData);

    //     return [
    //         'grand_total' => $grandTotalQty,
    //         ...$paginated->toArray(),
    //     ];
    // }


    private function resolveDateRange($dateRange): array
    {
        if (is_array($dateRange) && count($dateRange) === 2) {
            return [
                Carbon::parse($dateRange[0])->startOfDay()->toDateString(),
                Carbon::parse($dateRange[1])->endOfDay()->toDateString(),
            ];
        }
        return [Carbon::today()->toDateString(), Carbon::today()->toDateString()];
    }
}
