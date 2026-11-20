<?php

namespace App\Http\Controllers\Pos\Payment;

use App\Http\Controllers\Controller;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Balance\AdvanceCredit;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentHistoryController extends Controller
{
    // Allowed model whitelist — security এর জন্য
    private const array ALLOWED_MODEL_TYPES = [
        Purchase::class,
        Sale::class,
        SaleReturn::class,
        PurchaseReturn::class,

    ];

    public function index()
    {
        $perPage = request('per_page', 10);
        $search = request('search');

        $query = Ledger::query()
            ->with('contact:id,name,business_name,business_logo,profile_picture')
            ->when($search, function ($q) use ($search) {
                $q->where(('full_invoice_number'), 'LIKE', "%{$search}%")
                    ->orWhereHas('contact', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('business_name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%");
                    });
            });

        if (auth()->user()->isAppAdmin()) {
            // Admin: optional filter
            if (request('shop')) {
                $query->where('tenant_id', request('shop'));
            }
        } else {
            $query->where('tenant_id', tenant()->id);
        }

        return $query
            ->with('tenant:id,name')
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function debitVoucher(Ledger $ledger)
    {
        return $this->generateVoucherPDF($ledger, 'debit_voucher');
    }

    public function creditVoucher(Ledger $ledger)
    {
        return $this->generateVoucherPDF($ledger, 'credit_voucher');
    }

    public function generateVoucherPDF(Ledger $ledger, string $voucherType)
    {
        $ledgerData = $ledger->load('contact:id,name,business_name,phone,email,address', 'paymentMethod:id,name');
        $amount = $ledgerData->debit > 0
            ? $ledgerData->debit
            : ($ledgerData->credit > 0 ? $ledgerData->credit : 0);

        $amountInWords = $this->numberToWords($amount);
        if ($voucherType === 'debit_voucher') {
            $pdf = Pdf::loadView('pdf.voucher.debit_voucher', [
                'ledger' => $ledgerData,
                'amount' => $amount,
                'amountInWords' => $amountInWords,
            ]);

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$ledger->full_invoice_number.'.pdf"',
            ]);
        }
        if ($voucherType === 'credit_voucher') {
            $pdf = Pdf::loadView('pdf.voucher.credit_voucher', [
                'ledger' => $ledgerData,
                'amount' => $amount,
                'amountInWords' => $amountInWords,
            ]);

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$ledger->full_invoice_number.'.pdf"',
            ]);
        }
    }

    public function delete($ledgerId)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        $ledger = Ledger::query()
            ->when(! $isAppAdmin, fn ($q) => $q->where('tenant_id', tenant()->id))
            ->where('id', $ledgerId)
            ->first();

        if (! $ledger) {
            throw new Exception('Ledger entry not found');
        }

        $tenantId = $ledger->tenant_id;

        // Pivot related transactions (advance ledger এ এটা empty হতে পারে)
        $relatedTransactions = $ledger->transactions;

        $isPureAdvance = in_array($ledger->type, [
            'advance_received', // customer advance
            'advance_sent',     // supplier advance
        ]);

        // ✅ Fix: pure advance হলে transaction না থাকলেও চলবে
        if (! $isPureAdvance && $relatedTransactions->isEmpty()) {
            throw new Exception('No transactions found for this ledger entry');
        }

        try {
            DB::beginTransaction();

            // =====================================================
            // 1. Transaction সহ ledger মুছো
            // =====================================================
            if ($relatedTransactions->isNotEmpty()) {
                $affectedModels = $relatedTransactions->map(fn ($t) => [
                    'model_type' => $t->model_type,
                    'model_id' => $t->model_id,
                    'tenant_id' => $t->tenant_id,
                ])->unique(fn ($item) => $item['model_type'].'-'.$item['model_id']);

                $ledger->transactions()->detach();
                $relatedTransactions->each->delete();

                // Sale/Purchase status recalculate
                foreach ($affectedModels as $modelInfo) {
                    $this->recalculateByModel(
                        $modelInfo['model_type'],
                        $modelInfo['model_id'],
                        $modelInfo['tenant_id']
                    );
                }
            }

            // =====================================================
            // 2. ✅ Fix: Advance entry reverse করো
            // =====================================================
            if ($ledger->type === 'advance_received') {
                // Customer advance — receivable entry টা reverse করো
                AdvanceCredit::query()
                    ->where('contact_id', $ledger->contact_id)
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'receivable')
                    ->where('date', $ledger->date)
                    ->where('amount', $ledger->credit)
                    ->latest('id')
                    ->first()
                    ?->delete();
            }

            if ($ledger->type === 'advance_sent') {
                // Supplier advance — payable entry টা reverse করো
                AdvanceCredit::query()
                    ->where('contact_id', $ledger->contact_id)
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'payable')
                    ->where('date', $ledger->date)
                    ->where('amount', $ledger->debit)
                    ->latest('id')
                    ->first()
                    ?->delete();
            }

            // =====================================================
            // 3. Ledger delete
            // =====================================================
            $ledger->delete();

            DB::commit();

            return successResponse('Ledger entry deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();

            if (app()->environment('production')) {
                throw new Exception('Failed to delete ledger entry. Please try again later.', 500, $e);
            }

            throw new Exception($e->getMessage(), 500, $e);
        }
    }

    private function recalculateByModel(string $modelType, int $modelId, int $tenantId): void
    {
        if (! in_array($modelType, self::ALLOWED_MODEL_TYPES)) {
            throw new Exception("Invalid model type: {$modelType}");
        }

        $model = $modelType::query()
            ->where('id', $modelId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $model) {
            return;
        }

        $totalPaid = Transaction::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('tenant_id', $tenantId)
            ->sum('amount');

        $status = match (true) {
            round($totalPaid, 2) <= 0 => 'due',
            round($totalPaid, 2) >= round($model->grand_total, 2) => 'paid',
            default => 'partially_paid',
        };

        $model->update(['status' => $status]);
    }

    public function numberToWords(float $amount): string
    {
        $ones = [
            '',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen',
        ];

        $tens = [
            '',
            '',
            'Twenty',
            'Thirty',
            'Forty',
            'Fifty',
            'Sixty',
            'Seventy',
            'Eighty',
            'Ninety',
        ];

        $parts = explode('.', number_format($amount, 2, '.', ''));
        $intPart = (int) $parts[0];
        $decPart = (int) $parts[1];

        function convertGroup(int $n, array $ones, array $tens): string
        {
            $result = '';

            if ($n >= 100) {
                $result .= $ones[(int) ($n / 100)].' Hundred ';
                $n %= 100;
            }

            if ($n >= 20) {
                $result .= $tens[(int) ($n / 10)].' ';
                $n %= 10;
            }

            if ($n > 0) {
                $result .= $ones[$n].' ';
            }

            return $result;
        }

        function convert(int $number, array $ones, array $tens): string
        {
            if ($number === 0) {
                return 'Zero';
            }

            $result = '';

            if ($number >= 10_000_000) {
                $result .= convertGroup((int) ($number / 10_000_000), $ones, $tens).'Crore ';
                $number %= 10_000_000;
            }

            if ($number >= 100_000) {
                $result .= convertGroup((int) ($number / 100_000), $ones, $tens).'Lakh ';
                $number %= 100_000;
            }

            if ($number >= 1_000) {
                $result .= convertGroup((int) ($number / 1_000), $ones, $tens).'Thousand ';
                $number %= 1_000;
            }

            if ($number > 0) {
                $result .= convertGroup($number, $ones, $tens);
            }

            return $result;
        }

        $words = convert($intPart, $ones, $tens).'Taka';

        if ($decPart > 0) {
            $words .= ' And '.convert($decPart, $ones, $tens).'Paisa';
        }

        return trim($words).' Only';
    }
}
