<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Purchase\PurchaseFilter;
use App\Filters\Pos\Report\SupplierReportFilter;
use App\Filters\Pos\Shop\ShopDynamicTableFilter;
use App\Filters\Pos\Shop\ShopFilter;
use App\Filters\Pos\Transaction\TransactionSaleFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Supplier\Supplier;
use App\Models\Pos\Transaction\Transaction;
use Illuminate\Support\Facades\DB;

class SupplierReportController extends Controller
{
    private function transactionSum($modelClass, $search = null)
    {
        return Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph('model', [$modelClass], function ($query) use ($search) {
                if ($search) {
                    $query->whereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }
            })
            ->sum('amount');
    }

    public function index()
    {
        $search = request('search');

        $purchaseGrandTotal = Purchase::query()
            ->filters(new ShopFilter)
            ->when($search, fn ($query) => $query->whereHas('contacts', fn ($query) => $query->where('name', 'like', "%{$search}%")))
            ->sum('grand_total');

        $purchasesPaidAmount = $this->transactionSum(Purchase::class, $search);
        $purchasesDueAmount = $purchaseGrandTotal - $purchasesPaidAmount;

        $purchaseReturnsGrandTotal = PurchaseReturn::query()
            ->filters(new ShopFilter)
            ->when($search, fn ($query) => $query->whereHas('contacts', fn ($query) => $query->where('name', 'like', "%{$search}%")))
            ->sum('grand_total');

        $purchaseReturnsPaidAmount = $this->transactionSum(PurchaseReturn::class, $search);

        $purchaseReturnsDueAmount = $purchaseReturnsGrandTotal - $purchaseReturnsPaidAmount;

        $suppliers = Contact::query()
            ->filters(new SupplierReportFilter)
            ->whereHas('purchases')
            ->select([
                'contacts.id',
                'contacts.name',
                'contacts.phone',
                'contacts.business_name',

                // Total purchases count
                DB::raw('COALESCE(COUNT(purchases.id), 0) AS total_purchase'),

                // Total purchase amount
                // DB::raw('SUM(purchases.grand_total) AS grand_total'),

            ])
            ->leftJoin('purchases', 'purchases.contact_id', '=', 'contacts.id')
            ->leftJoin('purchase_returns', 'purchase_returns.contact_id', '=', 'contacts.id')

            // Purchase payments (Transaction model where model_type = Purchase)
            ->leftJoin('transactions as purchase_payments', function ($join) {
                $join->on('purchase_payments.model_id', '=', 'purchases.id')
                    ->where('purchase_payments.model_type', '=', DB::raw("'".addslashes(Purchase::class)."'"));
            })

            // Return payments (Transaction model where model_type = PurchaseReturn)
            ->leftJoin('transactions as return_payments', function ($join) {
                $join->on('return_payments.model_id', '=', 'purchase_returns.id')
                    ->where('return_payments.model_type', '=', DB::raw("'".addslashes(PurchaseReturn::class)."'"));
            })
            ->groupBy('contacts.id', 'contacts.name', 'contacts.phone')
            ->orderBy('contacts.id', 'desc')
            ->paginate(request('per_page', 10))
            ->through(fn ($supplier) => [
                ...$supplier->toArray(),
                'total_amount' => $supplier->getPaidAmountAttribute() + $supplier->getDueAmountAttribute(),
                'purchase_due_amount' => $supplier->getDueAmountAttribute(),
                'purchase_paid_amount' => $supplier->getPaidAmountAttribute(),
                'total_purchase_return_due_amount' => $supplier->getPurchaseReturnDueAmountAttribute(),
                'total_purchase_return_paid_amount' => $supplier->getPurchaseReturnPaidAmountAttribute(),
            ]);

        return [
            ...$suppliers->toArray(),
            'grand_total' => [
                'total_amount' => $purchaseGrandTotal,
                'purchase_paid_amount' => $purchasesPaidAmount,
                'purchase_due_amount' => $purchasesDueAmount,
                'total_purchase_return_paid_amount' => $purchaseReturnsPaidAmount,
                'total_purchase_return_due_amount' => $purchaseReturnsDueAmount,
            ],
        ];
    }

    public function supplierDetails($id)
    {
        $supplier = Supplier::query()
            ->select([
                'suppliers.id',
                'suppliers.name',
                'suppliers.phone',
                'suppliers.company_name',
                DB::raw('COALESCE(SUM(purchases.grand_total), 0) AS total_purchase_amount'),
                DB::raw('COALESCE(SUM(purchase_returns.grand_total), 0) AS total_return_amount'),
            ])
            ->leftJoin('purchases', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('purchase_returns', 'purchase_returns.supplier_id', '=', 'suppliers.id')
            ->where('suppliers.id', $id)
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.phone', 'suppliers.company_name')
            ->first();

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        // Calculate payments and dues
        $purchasePaid = DB::table('transactions')
            ->where('model_type', Purchase::class)
            ->whereIn('model_id', Purchase::query()->where('supplier_id', $id)->pluck('id'))
            ->sum('amount');

        $returnPaid = DB::table('transactions')
            ->where('model_type', PurchaseReturn::class)
            ->whereIn('model_id', PurchaseReturn::query()->where('supplier_id', $id)->pluck('id'))
            ->sum('amount');

        $supplier->purchase_paid_amount = $purchasePaid;
        $supplier->purchase_due_amount = max(0, $supplier->total_purchase_amount - $purchasePaid);
        $supplier->return_paid_amount = $returnPaid;
        $supplier->return_due_amount = max(0, $supplier->total_return_amount - $returnPaid);

        return response()->json($supplier);
    }

    public function supplierPurchases($supplierId)
    {
        $perPage = request('per_page', 10);

        $baseQuery = Purchase::query()
            ->filters(new PurchaseFilter)
            ->with(['contact', 'tenant'])
            ->where('contact_id', $supplierId)
            ->orderByDesc('id');

        // Paginate purchases
        $purchases = (clone $baseQuery)->paginate($perPage);

        // Calculate totals efficiently
        $grandTotal = (clone $baseQuery)->sum('grand_total');
        $paidTotal = (clone $baseQuery)->with('payments')->get()->sum(fn ($purchase) => $purchase->transaction_amount);
        $dueTotal = $grandTotal - $paidTotal;

        // Transform paginated collection
        $purchases->getCollection()->transform(function ($purchase) {
            $paid = $purchase->transaction_amount;
            $due = $purchase->grand_total - $paid;

            return [
                'id' => $purchase->id,
                'invoice_number' => $purchase->full_invoice_number,
                'date' => $purchase->date,
                'supplier_name' => $purchase->contact?->name,
                'shop_name' => $purchase->tenant?->name,
                'grand_total' => $purchase->grand_total,
                'paid' => $paid,
                'due' => $due,
                'status' => $purchase->status,
                'payment_status' => $paid >= $purchase->grand_total ? 'Paid' : 'Unpaid',
            ];
        });

        return [
            ...$purchases->toArray(),
            'grand_total' => [
                'grand_total' => floor($grandTotal),
                'paid' => floor($paidTotal),
                'due' => floor($dueTotal),
            ],
        ];
    }

    public function supplierPurchaseReturns($supplierId)
    {
        $perPage = request('per_page', 10);

        $query = PurchaseReturn::query()
            ->filters(new PurchaseFilter)
            ->with(['payments', 'contact', 'tenant'])
            ->where('contact_id', $supplierId);

        $grandTotal = $query->sum('grand_total');
        $paidAmount = $query->with('payments')->get()->sum(fn ($purchaseReturn) => $purchaseReturn->payments->sum('amount'));
        $dueAmount = $grandTotal - $paidAmount;

        // Paginate results
        $purchaseReturns = $query->orderByDesc('id')->paginate($perPage);

        // Transform paginated collection for API response
        $purchaseReturns->getCollection()->transform(function ($purchaseReturn) {
            $paid = $purchaseReturn->payments->sum('amount');
            $due = $purchaseReturn->grand_total - $paid;

            return [
                'id' => $purchaseReturn->id,
                'invoice_number' => $purchaseReturn->full_invoice_number,
                'date' => $purchaseReturn->date,
                'supplier_name' => $purchaseReturn->contact?->name,
                'shop_name' => $purchaseReturn->tenant?->name,
                'grand_total' => $purchaseReturn->grand_total,
                'paid' => $paid,
                'due' => $due,
                'status' => $purchaseReturn->status,
                'payment_status' => $paid >= $purchaseReturn->grand_total ? 'Paid' : 'Unpaid',
            ];
        });

        return [
            ...$purchaseReturns->toArray(),
            'grand_total' => [
                'grand_total' => floor($grandTotal),
                'paid' => floor($paidAmount),
                'due' => floor($dueAmount),
            ],
        ];
    }

    public function purchaseTransactionBySupplier($supplierId)
    {
        $perPage = request('per_page', 10);

        // Base query for transactions
        $query = Transaction::query()
            ->filters(new TransactionSaleFilter)
            ->purchases()
            ->where('model_type', Purchase::class)
            ->whereIn('model_id', function ($query) use ($supplierId) {
                $query->select('id')
                    ->from('purchases')
                    ->where('contact_id', $supplierId);
            })
            ->with([
                'model:id,contact_id,status,grand_total',
                'model.contact:id,name',
                'paymentMethod:id,name',
            ]);

        // Calculate totals for all matching transactions
        $allTransactions = $query->get();

        $grandTotal = $allTransactions->sum(fn ($item) => $item->model?->grand_total ?? 0);
        $paidAmount = $allTransactions->sum(fn ($item) => $item->amount ?? 0);
        $dueAmount = $grandTotal - $paidAmount;

        // Paginate results for API
        $transactions = $query->orderByDesc('id')->paginate($perPage);

        // Transform paginated collection
        $transactions->getCollection()->transform(function ($item) {
            $due = ($item->model?->grand_total ?? 0) - ($item->amount ?? 0);

            return [
                'id' => $item->id,
                'date' => $item->date,
                'invoice_number' => $item->full_invoice_number ?? null,
                'supplier_name' => $item->model?->contact?->name ?? null,
                'payment_method' => $item->paymentMethod?->name ?? null,
                'grand_total' => $item->model?->grand_total ?? 0,
                'paid' => $item->amount ?? 0,
                'due' => $due,
                'status' => $item->model?->status,
            ];
        });

        // Return paginated data along with totals
        return [
            ...$transactions->toArray(),
            'grand_total' => [
                'grand_total' => floor($grandTotal),
                'paid' => floor($paidAmount),
                'due' => floor($dueAmount),
            ],
        ];
    }

    public function purchaseReturnTransactionBySupplier($supplierId)
    {
        $perPage = request('per_page', 10);

        // Base query
        $query = Transaction::query()
            ->filters(new TransactionSaleFilter)
            ->purchaseReturns()
            ->where('model_type', PurchaseReturn::class)
            ->whereIn('model_id', function ($query) use ($supplierId) {
                $query->select('id')
                    ->from('purchase_returns')
                    ->where('contact_id', $supplierId);
            })
            ->with([
                'model:id,contact_id,status,grand_total',
                'model.contact:id,name',
                'paymentMethod:id,name',
            ]);

        // Calculate totals for all matching transactions
        $allTransactions = $query->get();

        $grandTotal = $allTransactions->sum(fn ($item) => $item->model?->grand_total ?? 0);
        $paidAmount = $allTransactions->sum(fn ($item) => $item->amount ?? 0);
        $dueAmount = $grandTotal - $paidAmount;

        // Paginate results for API
        $transactions = $query->orderByDesc('id')->paginate($perPage);

        // Transform paginated collection
        $transactions->getCollection()->transform(fn ($item) => [
            'id' => $item->id,
            'date' => $item->date,
            'invoice_number' => $item->full_invoice_number ?? null,
            'supplier_name' => $item->model?->contact?->name ?? null,
            'payment_method' => $item->paymentMethod?->name ?? null,
            'grand_total' => $item->model?->grand_total ?? 0,
            'paid' => $item->amount ?? 0,
            'due' => ($item->model?->grand_total ?? 0) - ($item->amount ?? 0),
            'status' => $item->model?->status,
        ]);

        // Return paginated data along with totals
        return [
            ...$transactions->toArray(),
            'grand_total' => [
                'grand_total' => floor($grandTotal),
                'paid' => floor($paidAmount),
                'due' => floor($dueAmount),
            ],
        ];
    }

    public function supplierLedgerDetail($supplierId)
    {
        $perPage = request('per_page', 50); // Default per page

        // Purchases → Debit
        $purchases = Purchase::query()
            ->filters(new ShopDynamicTableFilter('purchases'))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                DB::raw("'Purchase' as type"),
                DB::raw('grand_total as debit'),
                DB::raw('0 as credit')
            )
            ->whereNull('deleted_at')
            ->where('supplier_id', $supplierId);

        $purchaseReturns = PurchaseReturn::query()
            ->filters(new ShopDynamicTableFilter('purchase_returns'))
            ->select(
                'id',
                'date',
                'full_invoice_number',
                DB::raw("'Purchase Return' as type"),
                DB::raw('0 as debit'),
                DB::raw('grand_total as credit')
            )
            ->whereNull('deleted_at')
            ->where('supplier_id', $supplierId);

        //  Payments Sent → Credit
        $paymentsSent = Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->join('purchases as p', 'transactions.model_id', '=', 'p.id')
            ->select(
                'transactions.id',
                'transactions.date',
                DB::raw('COALESCE(transactions.full_invoice_number, "") as full_invoice_number'),
                DB::raw("'Payment Sent' as type"),
                DB::raw('0 as debit'),
                DB::raw('transactions.amount as credit')
            )
            ->where('transactions.model_type', Purchase::class)
            ->where(function ($q) use ($supplierId) {
                $q->where('p.supplier_id', $supplierId)
                    ->orWhereNull('p.id');
            });

        //  Payments Received → Debit
        $paymentsReceived = Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->join('purchase_returns as pr', 'transactions.model_id', '=', 'pr.id')
            ->select(
                'transactions.id',
                'transactions.date',
                DB::raw('COALESCE(transactions.full_invoice_number, "") as full_invoice_number'),
                DB::raw("'Payment Received' as type"),
                DB::raw('transactions.amount as debit'), // ✅ correct
                DB::raw('0 as credit')
            )
            ->where('transactions.model_type', PurchaseReturn::class)
            ->where(function ($q) use ($supplierId) {
                $q->where('pr.supplier_id', $supplierId)
                    ->orWhereNull('pr.id');
            });

        //  Combine all entries
        $ledgerQuery = $purchases
            ->unionAll($purchaseReturns)   // ✅ ADD THIS
            ->unionAll($paymentsSent)
            ->unionAll($paymentsReceived);
        //  Add running balance
        $search = request('search');
        $data = DB::query()
            ->when($search, fn ($query) => $query->where('full_invoice_number', 'LIKE', '%'.$search.'%'))
            ->fromSub($ledgerQuery, 'ledger')
            ->select(
                'id',
                'full_invoice_number',
                'date',
                'type',
                'debit',
                'credit',
                DB::raw('
                SUM(debit - credit)
                OVER (ORDER BY date, id ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                AS balance
            ')
            )
            ->paginate($perPage);

        // Grand totals
        $totalDebit = Purchase::query()
            ->filters(new ShopDynamicTableFilter('purchases'))
            ->when($search, fn ($query) => $query->where('full_invoice_number', 'LIKE', '%'.$search.'%'))
            ->where('supplier_id', $supplierId)
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $totalCredit = Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->when($search, fn ($query) => $query->where('transactions.full_invoice_number', 'LIKE', '%'.$search.'%'))
            ->leftJoin('purchase_returns as pr', 'transactions.model_id', '=', 'pr.id')
            ->where('transactions.model_type', PurchaseReturn::class)
            ->where('pr.supplier_id', $supplierId)
            ->sum('transactions.amount'); // Payment Received → Debit

        $totalCredit += Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->when($search, fn ($query) => $query->where('transactions.full_invoice_number', 'LIKE', '%'.$search.'%'))
            ->leftJoin('purchases as p', 'transactions.model_id', '=', 'p.id')
            ->where('transactions.model_type', Purchase::class)
            ->where('p.supplier_id', $supplierId)
            ->sum('transactions.amount'); // Payment Sent → Credit

        $closingBalance = $totalDebit - $totalCredit;

        // Return JSON
        return response()->json([
            ...$data->toArray(),
            'grand_total' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balance' => $closingBalance,
            ],
        ]);
    }
}
