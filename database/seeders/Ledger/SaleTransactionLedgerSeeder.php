<?php

namespace Database\Seeders\Ledger;

use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\Transaction\Transaction;
use App\Scopes\TenantScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleTransactionLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = Transaction::query()
            ->withoutGlobalScope(new TenantScope)
            ->join('sales', function ($join) {
                $join->on('transactions.model_id', '=', 'sales.id')
                    ->where('transactions.model_type', Sale::class);
            })
            ->select(
                'transactions.tenant_id',
                'transactions.date',
                'sales.contact_id',
                DB::raw('SUM(transactions.amount) as total_amount'),
                DB::raw('GROUP_CONCAT(transactions.id) as transaction_ids'),
                DB::raw('GROUP_CONCAT(transactions.note) as notes'),
                DB::raw('GROUP_CONCAT(transactions.payment_method_id) as payment_methods')
            )
            ->groupBy(
                'transactions.tenant_id',
                'transactions.date',
                'sales.contact_id',
            )->get();

        $transactions->each(function ($transaction) {

            $exists = Ledger::query()
                ->where('tenant_id', $transaction->tenant_id)
                ->where('date', $transaction->date)
                ->where('contact_id', $transaction->contact_id)
                ->where('type', 'payment_received')
                ->exists();

            if (! $exists) {
                // ✅ clean notes
                $notes = collect(explode(',', $transaction->notes))
                    ->filter()
                    ->unique()
                    ->implode(', ');

                // ✅ payment method handling
                $paymentMethodIds = collect(explode(',', $transaction->payment_methods))
                    ->filter()
                    ->unique();

                $paymentMethodId = $paymentMethodIds->count() === 1
                    ? $paymentMethodIds->first()
                    : null;

                if ($paymentMethodIds->count() > 1) {
                    $notes .= ' (Multiple payment methods)';
                }

                $lastInvoice = Ledger::query()->max('invoice_number') ?? 0;

                $ledger = Ledger::query()->create([
                    'date' => $transaction->date,
                    'invoice_number' => $lastInvoice + 1,
                    'full_invoice_number' => 'SLT-'.$lastInvoice + 1,
                    'contact_id' => $transaction->contact_id,
                    'tenant_id' => $transaction->tenant_id,
                    'payment_method_id' => $paymentMethodId,
                    'note' => $notes,
                    'debit' => 0,
                    'credit' => $transaction->total_amount,
                    'type' => 'payment_received',
                ]);

                // pivot insert
                $transactionIds = explode(',', $transaction->transaction_ids);

                $ledger->transactions()->attach($transactionIds);
            }
        });
    }
}
