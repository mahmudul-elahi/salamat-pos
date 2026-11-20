<?php

namespace App\Http\Controllers\Pos\Customer;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Customer\CustomerPaymentRequest;
use App\Models\Customer\Customer;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Income\IncomeCategory;
use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Supplier\Supplier;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Balance\AdvanceCredit;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerPaymentController extends Controller
{
    public function getDue($customerId)
    {
        // One query to sum sales
        $totalSales = Sale::query()->where('customer_id', $customerId)
            ->where('status', '!=', 'return')->sum('grand_total');

        // Combined query for all payments (Sale-based + Direct Customer based)
        $totalPaid = Transaction::query()->where(function ($q) use ($customerId) {
            $q->where('model_type', Sale::class)
                ->whereIn('model_id', Sale::query()->where('customer_id', $customerId)->pluck('id'));
        })->orWhere(function ($q) use ($customerId) {
            $q->where('model_type', Customer::class)->where('model_id', $customerId);
        })->sum('amount');

        return response()->json([
            'due_amount' => $totalSales - $totalPaid,
            'breakdown' => ['total_sales' => $totalSales, 'total_paid' => $totalPaid],
        ]);
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function store(CustomerPaymentRequest $request)
    {
        $data = $request->validated();
        $tenantId = $data['tenant_id'] ?? tenant()->id;

        try {
            DB::beginTransaction();

            // Normal customer payment (due sales + extra advance)
            $ledger = $this->applyCustomerPayment($data);

            // --- Payment Method ---
            $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

            // --- Income Category ---
            $incomeCategory = IncomeCategory::query()
                ->where('director_id', $paymentMethod->director_id)
                ->first();

            // --- Create Income ---
            if ($incomeCategory) {
                $contact = Contact::findOrFail($data['contact_id']);

                Income::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $paymentMethod->director_id,
                    'title' => 'Payment from '.$contact->name.' via '.$paymentMethod->name,
                    'date' => $data['date'],
                    'income_category_id' => $incomeCategory->id,
                    'amount' => $data['amount'],
                    'note' => $data['note'] ?? null,
                    'contact_id' => $data['contact_id'],
                    'ledger_id' => $ledger->id,
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return successResponse('Payment processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Payment has been failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    // =========================================================
    // APPLY CUSTOMER PAYMENT
    // Fix: ledgerType dynamically updated after loop
    // =========================================================
    public function applyCustomerPayment(array $data)
    {
        $tenantId = $data['tenant_id'] ?? tenant()->id;
        $newPayment = (float) $data['amount'];

        // =====================================================
        // 1. Advance balance
        // =====================================================
        $advanceBalance = (float) AdvanceCredit::query()
            ->whereIn('type', ['receivable', 'used'])
            ->where('contact_id', $data['contact_id'])
            ->where('tenant_id', $tenantId)
            ->sum('amount');

        $advanceBalance = max(0, $advanceBalance);
        $payAmount = $advanceBalance + $newPayment;
        $remainingAdvance = $advanceBalance;
        $totalUsedFromAdvance = 0;

        // =====================================================
        // 2. Due sales লোড
        // Always scoped to $tenantId explicitly (not just when isAdmin) -
        // the ledger entry below always records this exact tenant_id, so the
        // due-sales lookup has to match it too. Left conditional, a non-admin
        // whose active session tenant differs from $tenantId would silently
        // fall back to Sale's own TenantScope (their session tenant instead),
        // find zero due sales, and the payment would just sit in the ledger
        // with no sale ever marked paid.
        // =====================================================
        $sales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $data['contact_id'])
            ->whereIn('status', ['due', 'partially_paid'])
            ->withSum(['payments' => fn ($q) => $q->where('model_type', Sale::class)], 'amount')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // =====================================================
        // 3. Ledger entry
        // Fix: ledgerType determined AFTER loop to handle surplus correctly
        // Start with 'payment_received'; update to 'advance_received' if needed
        // =====================================================
        $ledgerLastInvoice = Ledger::query()->max('invoice_number') ?? 0;
        $invoiceNumber = $ledgerLastInvoice + 1;

        // Temporary type — will update below if needed
        $ledger = Ledger::query()->create([
            'date' => $data['date'],
            'invoice_number' => $invoiceNumber,
            'full_invoice_number' => 'PMT-'.$invoiceNumber,
            'contact_id' => $data['contact_id'],
            'tenant_id' => $tenantId,
            'payment_method_id' => $data['payment_method_id'],
            'debit' => 0,
            'credit' => $newPayment,
            'type' => 'payment_received',
            'note' => $data['note'] ?? null,
        ]);

        // =====================================================
        // 4. Transaction invoice number
        // =====================================================
        $latestInv = Transaction::query()
            ->withoutGlobalScope(TenantScope::class)
            ->max('invoice_number') ?? 0;

        // =====================================================
        // 5. Due sales pay করো
        // =====================================================
        foreach ($sales as $sale) {
            if ($payAmount <= 0.01) {
                break;
            }

            $due = $sale->grand_total - ($sale->payments_sum_amount ?? 0);
            if ($due <= 0.01) {
                continue;
            }

            $amount = min($payAmount, $due);

            $fromAdvance = min($remainingAdvance, $amount);
            $remainingAdvance -= $fromAdvance;
            $totalUsedFromAdvance += $fromAdvance;

            $this->createTransaction(
                $data,
                ++$latestInv,
                $amount,
                Sale::class,
                $sale->id,
                $tenantId,
                $ledger
            );

            $newPaid = ($sale->payments_sum_amount ?? 0) + $amount;
            $sale->update([
                'status' => $newPaid >= $sale->grand_total ? 'paid' : 'partially_paid',
            ]);

            $payAmount -= $amount;
        }

        // =====================================================
        // 6. ব্যবহৃত advance consume entry
        // =====================================================
        if ($totalUsedFromAdvance > 0.01) {
            AdvanceCredit::query()->create([
                'tenant_id' => $tenantId,
                'contact_id' => $data['contact_id'],
                'date' => $data['date'],
                'type' => 'used',
                'amount' => -$totalUsedFromAdvance,
                'note' => 'Advance auto-applied with payment',
            ]);
        }

        // =====================================================
        // 7. Surplus → new advance
        // =====================================================
        $surplusFromNewPayment = max(0, $payAmount - $remainingAdvance);

        if ($surplusFromNewPayment > 0.01) {
            AdvanceCredit::query()->create([
                'tenant_id' => $tenantId,
                'contact_id' => $data['contact_id'],
                'date' => $data['date'],
                'type' => 'receivable',
                'amount' => $surplusFromNewPayment,
                'note' => 'Advance received',
            ]);
        }

        // =====================================================
        // ✅ Fix: Update ledger type if no sales were paid (pure advance)
        // =====================================================
        if ($sales->isEmpty() || $surplusFromNewPayment >= $newPayment) {
            $ledger->update([
                'type' => 'advance_received',
                'full_invoice_number' => 'ADV-'.$invoiceNumber,
            ]);
        }

        return $ledger;
    }

    public function supplierPaymentSend(Request $request)
    {
        $isAdmin = auth()->user()->isAppAdmin();

        $data = $request->validate([
            'tenant_id' => [$isAdmin ? 'required' : 'nullable', 'exists:tenants,id'],
            'contact_id' => 'required|exists:contacts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = $data['tenant_id'] ?? tenant()->id;

            // --- Supplier Payment (due purchases + advance support) ---
            $ledger = $this->applySupplierPayment($data);

            // --- Resolve user_id and investment_category_id from PaymentMethod ---
            $paymentMethod = PaymentMethod::query()
                ->where('id', $data['payment_method_id'])
                ->first();

            $investmentCategory = InvestmentCategory::query()
                ->where('director_id', $paymentMethod?->director_id)
                ->first();

            if ($investmentCategory) {
                $title = 'Payment from '.$investmentCategory?->director?->name.' on '.$data['date'];

                Investment::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $paymentMethod?->director_id,
                    'title' => $title,
                    'date' => $data['date'],
                    'investment_category_id' => $investmentCategory?->id,
                    'amount' => $data['amount'],
                    'note' => $data['note'] ?? null,
                    'contact_id' => $data['contact_id'],
                    'ledger_id' => $ledger?->id,
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return successResponse('Payment sent successfully');
        } catch (GeneralException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Payment has been failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    // =========================================================
    // APPLY SUPPLIER PAYMENT
    // Fix: ledgerType dynamically updated after loop
    // =========================================================
    public function applySupplierPayment(array $data)
    {
        $tenantId = $data['tenant_id'] ?? tenant()->id;
        $newPayment = (float) $data['amount'];

        // =====================================================
        // 1. Existing supplier advance balance
        // =====================================================
        $advanceBalance = (float) AdvanceCredit::query()
            ->whereIn('type', ['payable', 'supplier_used'])
            ->where('contact_id', $data['contact_id'])
            ->where('tenant_id', $tenantId)
            ->sum('amount');

        $advanceBalance = max(0, $advanceBalance);
        $payAmount = $advanceBalance + $newPayment;
        $remainingAdvance = $advanceBalance;
        $totalUsedFromAdvance = 0;

        // =====================================================
        // 2. Due purchases লোড
        // Always scoped to $tenantId explicitly (not just when isAdmin) -
        // the ledger entry below always records this exact tenant_id, so the
        // due-purchases lookup has to match it too. Left conditional, a
        // non-admin whose active session tenant differs from $tenantId would
        // silently fall back to Purchase's own TenantScope (their session
        // tenant instead), find zero due purchases, and the payment would
        // just sit in the ledger with no purchase ever marked paid - exactly
        // what happened with PP-3322.
        // =====================================================
        $purchases = Purchase::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $data['contact_id'])
            ->whereIn('status', ['due', 'partially_paid'])
            ->withSum(['payments' => fn ($q) => $q->where('model_type', Purchase::class)], 'amount')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // =====================================================
        // 3. Ledger entry
        // Fix: start with 'payment_sent'; update to 'advance_sent' if needed
        // =====================================================
        $ledgerLastInvoice = Ledger::query()->max('invoice_number') ?? 0;
        $invoiceNumber = $ledgerLastInvoice + 1;

        $ledger = Ledger::query()->create([
            'date' => $data['date'],
            'invoice_number' => $invoiceNumber,
            'full_invoice_number' => 'PP-'.$invoiceNumber,
            'contact_id' => $data['contact_id'],
            'tenant_id' => $tenantId,
            'payment_method_id' => $data['payment_method_id'],
            'debit' => $newPayment,
            'credit' => 0,
            'type' => 'payment_sent',
            'note' => $data['note'] ?? null,
        ]);

        // =====================================================
        // 4. Transaction invoice number
        // =====================================================
        $latestInv = Transaction::query()
            ->withoutGlobalScope(TenantScope::class)
            ->max('invoice_number') ?? 0;

        // =====================================================
        // 5. Due purchases pay করো
        // =====================================================
        foreach ($purchases as $purchase) {
            if ($payAmount <= 0.01) {
                break;
            }

            $due = $purchase->grand_total - ($purchase->payments_sum_amount ?? 0);
            if ($due <= 0.01) {
                continue;
            }

            $amount = min($payAmount, $due);

            $fromAdvance = min($remainingAdvance, $amount);
            $remainingAdvance -= $fromAdvance;
            $totalUsedFromAdvance += $fromAdvance;

            $this->createTransaction(
                $data,
                ++$latestInv,
                $amount,
                Purchase::class,
                $purchase->id,
                $tenantId,
                $ledger
            );

            $newPaid = ($purchase->payments_sum_amount ?? 0) + $amount;
            $purchase->update([
                'status' => $newPaid >= $purchase->grand_total ? 'paid' : 'partially_paid',
            ]);

            $payAmount -= $amount;
        }

        // =====================================================
        // 6. ব্যবহৃত advance consume entry (negative)
        // =====================================================
        if ($totalUsedFromAdvance > 0.01) {
            AdvanceCredit::query()->create([
                'tenant_id' => $tenantId,
                'contact_id' => $data['contact_id'],
                'date' => $data['date'],
                'type' => 'supplier_used',
                'amount' => -$totalUsedFromAdvance,
                'note' => 'Supplier advance auto-applied with payment',
            ]);
        }

        // =====================================================
        // 7. বাকি টাকা নতুন supplier advance হিসেবে save
        // =====================================================
        $surplusFromNewPayment = max(0, $payAmount - $remainingAdvance);

        if ($surplusFromNewPayment > 0.01) {
            AdvanceCredit::query()->create([
                'tenant_id' => $tenantId,
                'contact_id' => $data['contact_id'],
                'date' => $data['date'],
                'type' => 'payable',
                'amount' => $surplusFromNewPayment,
                'note' => 'Supplier advance paid',
            ]);
        }

        // =====================================================
        // ✅ Fix: Update ledger type if no purchases were paid (pure advance)
        // =====================================================
        if ($purchases->isEmpty() || $surplusFromNewPayment >= $newPayment) {
            $ledger->update([
                'type' => 'advance_sent',
                'full_invoice_number' => 'SADV-'.$invoiceNumber,
            ]);
        }

        return $ledger;
    }

    private function createTransaction($data, $invNum, $amount, $model, $modelId, $tenantId, $ledger)
    {
        // ✅ Fix: was always 'SLT-' even for customer payments
        $prefix = $model === Sale::class ? 'CLT-' : 'SLT-';

        $transaction = Transaction::query()->create([
            'date' => $data['date'],
            'invoice_number' => $invNum,
            'full_invoice_number' => $prefix.$invNum,
            'payment_method_id' => $data['payment_method_id'],
            'amount' => $amount,
            'tenant_id' => $tenantId,
            'note' => $data['note'],
            'model_type' => $model,
            'model_id' => $modelId,
        ]);

        if ($ledger) {
            $ledger->transactions()->attach($transaction->id);
        }
    }

    public function index()
    {
        $search = request('search');
        $shop = request('shop');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId = $isAppAdmin ? $shop : tenant()->id;

        // Step 1: Fetch paginated contacts (with optional search)
        $contacts = Contact::query()
            // ->when($isAppAdmin && $tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where(function ($query) {
                $query->whereHas('sales')
                    ->orWhereHas('purchases');
            })
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('business_name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));

        // Step 2: Extract all contact IDs from this page
        $contactIds = $contacts->pluck('id');

        // Step 3: Bulk-fetch all totals in one call per model
        $totals = $this->getAllTotalsBulk($contactIds, $tenantId);

        // Step 4: Map totals onto each contact
        return $contacts->through(function ($item) use ($totals) {
            $t = $totals[$item->id] ?? [
                'debit' => 0,
                'credit' => 0,
            ];

            return [
                ...$item->toArray(),
                'total_debit' => $t['debit'],
                'total_credit' => $t['credit'],
                'balance' => $t['debit'] - $t['credit'],
            ];
        });
    }

    // =========================
    // BULK TOTALS (all contacts at once — eliminates N+1)
    // =========================
    private function getAllTotalsBulk($contactIds, $tenantId = null): array
    {
        // Sale => debit
        $saleTotals = Sale::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(grand_total) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Sale Return => credit
        $saleReturnTotals = SaleReturn::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(grand_total) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Purchase => credit
        $purchaseTotals = Purchase::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(grand_total) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Purchase Return => debit
        $purchaseReturnTotals = PurchaseReturn::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(grand_total) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Exchange purchases (Purchase with items->purchase_type = exchange) => debit
        $exchangeTotals = Purchase::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->whereHas('items', fn ($q) => $q->where('purchase_type', 'exchange'))
            ->selectRaw('contact_id, SUM(grand_total) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // Ledger => debit & credit (payment_received, payment_sent, return_payment_sent, etc.)
        $ledgerDebitTotals = Ledger::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(debit) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        $ledgerCreditTotals = Ledger::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('contact_id', $contactIds)
            ->selectRaw('contact_id, SUM(credit) as total')
            ->groupBy('contact_id')
            ->pluck('total', 'contact_id');

        // --- Assemble per-contact totals ---
        $result = [];

        foreach ($contactIds as $contactId) {
            $debit = ($saleTotals[$contactId] ?? 0)
                + ($purchaseReturnTotals[$contactId] ?? 0)
                + ($exchangeTotals[$contactId] ?? 0)
                + ($ledgerDebitTotals[$contactId] ?? 0);

            $credit = ($saleReturnTotals[$contactId] ?? 0)
                + ($purchaseTotals[$contactId] ?? 0)
                + ($ledgerCreditTotals[$contactId] ?? 0);

            $result[$contactId] = [
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return $result;
    }
}
