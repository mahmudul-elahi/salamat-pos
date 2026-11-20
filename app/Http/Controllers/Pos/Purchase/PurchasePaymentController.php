<?php

namespace App\Http\Controllers\Pos\Purchase;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Payment\PaymentRequest;
use App\Models\Pos\Expense\Expense;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Transaction\Transaction;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchasePaymentController extends Controller
{
    /**
     * Handle purchase payment.
     *
     * @throws Throwable
     */
    public function payment(PaymentRequest $request, Purchase $purchase)
    {
        return $this->handlePayment(
            request: $request,
            model: $purchase,
            prefix: 'PRP-',
        );
    }

    /**
     * Handle sale payment.
     *
     * @throws Throwable
     */
    public function salePayment(PaymentRequest $request, Sale $sale)
    {
        return $this->handlePayment(
            request: $request,
            model: $sale,
            prefix: 'SRT-'
        );
    }

    /**
     * Handle sale return payment.
     *
     * @throws Throwable
     */
    public function saleReturnPayment(PaymentRequest $request, SaleReturn $saleReturn)
    {
        return $this->handlePayment(
            request: $request,
            model: $saleReturn,
            prefix: 'SRRT-'
        );
    }

    /**
     * Handle expense payment.
     *
     * @throws Throwable
     */
    public function expensePayment(PaymentRequest $request, Expense $expense)
    {
        return $this->handlePayment(
            request: $request,
            model: $expense,
            prefix: 'EXP-'
        );
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function incomePayment(PaymentRequest $request, Income $income)
    {
        return $this->handlePayment(
            request: $request,
            model: $income,
            prefix: 'INE-'
        );
    }

    /**
     * Handle purchase return payment.
     *
     * @throws Throwable
     */
    public function purchaseReturnPayment(PaymentRequest $request, PurchaseReturn $purchaseReturn)
    {
        return $this->handlePayment(
            request: $request,
            model: $purchaseReturn,
            prefix: 'PRRP-'
        );
    }

    /**
     * Centralized payment handler for both Purchase and Sale.
     *
     * @throws Throwable
     * @throws GeneralException
     */
    protected function handlePayment(PaymentRequest $request, $model, string $prefix)
    {
        $this->validatePaymentDate($model->date, $request->date);

        if ($model->status === 'paid') {
            throw new GeneralException('The record is already paid.', 400);
        }

        $dueAmount = $this->getDueAmount($model);

        if (round($dueAmount, 2) < round($request->amount, 2)) {
            throw ValidationException::withMessages(['amount' => 'Exceed Amount! Please put the correct amount.']);
        }
        $invoiceNumber = $this->nextInvoiceNumber();
        if (auth()->user()->isAppAdmin()) {
            $request->merge(['tenant_id' => $model->tenant_id]);
        }
        try {
            DB::beginTransaction();

            $model->payments()->create([
                'date' => $request->date,
                'invoice_number' => $invoiceNumber,
                'full_invoice_number' => $prefix.$invoiceNumber,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->amount,
                'tenant_id' => $model->tenant_id,
                'note' => $request->note,
            ]);

            if ($model instanceof Expense) {
                $remainingDue = $model->amount - $this->getPaidAmount($model);
            } elseif ($model instanceof Income) {
                $remainingDue = $model->amount - $this->getPaidAmount($model);
            } else {
                $remainingDue = $model->grand_total - $this->getPaidAmount($model);
            }

            $model->update([
                'status' => $remainingDue == 0 ? 'paid' : 'partially_paid',
            ]);

            DB::commit();

            return successResponse('Payment created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Payment creation failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Validate that payment date is not earlier than model date.
     *
     * @throws GeneralException
     */
    protected function validatePaymentDate($modelDate, $paymentDate): void
    {
        if (Carbon::parse($modelDate)->gt(Carbon::parse($paymentDate))) {
            throw new GeneralException("The 'Received On' date can't be earlier than the record date.", 400);
        }
    }

    /**
     * Get due amount for a model.
     */
    protected function getDueAmount($model): float
    {
        if ($model instanceof Purchase) {
            return $model->grand_total - $model->getDueAmountAttribute();
        }

        if ($model instanceof PurchaseReturn) {
            return $model->grand_total - $model->getDueAmountAttribute();
        }

        if ($model instanceof Sale) {
            return $model->grand_total - $model->getTransactionAmountAttribute();
        }

        if ($model instanceof SaleReturn) {
            return $model->grand_total - $model->getTransactionAmountAttribute();
        }
        if ($model instanceof Expense) {
            return $model->amount - $model->getTransactionAmountAttribute();
        }
        if ($model instanceof Income) {
            return $model->amount - $model->getTransactionAmountAttribute();
        }

        return $model->grand_total;
    }

    /**
     * Get total paid amount for a model.
     */
    protected function getPaidAmount($model): float
    {
        if ($model instanceof Purchase) {
            return $model->payments()->sum('amount');
        }

        if ($model instanceof PurchaseReturn) {
            return $model->payments()->sum('amount');
        }

        if ($model instanceof Sale) {
            return $model->getTransactionAmountAttribute();
        }

        if ($model instanceof SaleReturn) {
            return $model->getTransactionAmountAttribute();
        }

        if ($model instanceof Expense) {
            return $model->getTransactionAmountAttribute();
        }
        if ($model instanceof Income) {
            return $model->getTransactionAmountAttribute();
        }

        return 0.0;
    }

    /**
     * Get the next invoice number for a model type.
     */
    protected function nextInvoiceNumber(): int
    {
        return Transaction::query()
            ->withoutGlobalScope(new TenantScope)
            ->max('invoice_number') + 1;
    }
}
