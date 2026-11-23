<?php

namespace App\Console\Commands\Ledger;

use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Balance\AdvanceCredit;
use App\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds Ledger payment entries (payment_sent / payment_received) that have no
 * linked Transaction - money that was recorded as paid/received but never
 * actually applied against a sale/purchase, or logged as an advance. This
 * happens when the due-lookup that CustomerPaymentController runs at payment
 * time returns nothing (e.g. the tenant-scope bug fixed alongside this
 * command, or the older "double entry" bug fixed 2026-05-19) even though the
 * ledger entry itself still gets created.
 *
 * For each one found, it replays the same allocation the original payment
 * should have done: pay off the contact's oldest due sales/purchases first
 * (FIFO, same as CustomerPaymentController), and whatever's left over goes to
 * AdvanceCredit - exactly mirroring applyCustomerPayment()/applySupplierPayment().
 * If the contact has no due items at all under that tenant right now, the
 * whole amount is treated as a pure advance and the ledger is relabelled
 * (advance_received/advance_sent) to match what the live flow does in that
 * case - we can no longer know for certain which invoice it was for, so
 * advance is the only non-destructive interpretation left.
 *
 * Defaults to a dry run - nothing is written unless --apply is passed.
 */
class ReconcileLedgerTransactions extends Command
{
    #[\Override]
    protected $signature = 'ledger:reconcile-missing-transactions
        {--apply : Actually write the changes. Without this, nothing is saved - only reported.}
        {--id=* : Only process these specific ledger id(s) instead of every broken one found.}';

    #[\Override]
    protected $description = 'Reconcile Ledger payment entries that have no linked Transaction, by applying them against the contact\'s currently due sales/purchases (or advance credit if none are due).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $onlyIds = array_map(intval(...), $this->option('id'));

        $broken = Ledger::withoutGlobalScope(TenantScope::class)
            ->whereIn('type', ['payment_sent', 'payment_received'])
            ->whereDoesntHave('transactions')
            ->when($onlyIds, fn ($q) => $q->whereIn('id', $onlyIds))
            ->with('contact:id,name')
            ->orderBy('id')
            ->get();

        $this->info(($apply ? 'APPLYING CHANGES' : 'DRY RUN (pass --apply to actually write)').' - '.$broken->count().' ledger entr'.($broken->count() === 1 ? 'y' : 'ies').' to process.');
        $this->newLine();

        if ($broken->isEmpty()) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $nextInvoiceNumber = Transaction::query()->withoutGlobalScope(TenantScope::class)->max('invoice_number') ?? 0;

        $rows = [];
        $summary = ['matched' => 0, 'advance' => 0, 'skipped' => 0];

        foreach ($broken as $ledger) {
            DB::beginTransaction();
            try {
                [$category, $detail] = $this->reconcileOne($ledger, $apply, $nextInvoiceNumber);
                $summary[$category]++;
                $amount = (float) ($ledger->type === 'payment_received' ? $ledger->credit : $ledger->debit);
                $rows[] = [$ledger->id, $ledger->full_invoice_number, $ledger->type, $ledger->contact->name ?? '-', number_format($amount, 2), $category, $detail];

                if ($apply) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $rows[] = [$ledger->id, $ledger->full_invoice_number, $ledger->type, $ledger->contact->name ?? '-', '-', 'ERROR', $e->getMessage()];
            }
        }

        $this->table(['Ledger ID', 'Invoice', 'Type', 'Contact', 'Amount', 'Result', 'Detail'], $rows);
        $this->newLine();
        $this->info("Matched against due items: {$summary['matched']}");
        $this->info("Treated as pure advance: {$summary['advance']}");
        $this->info("Skipped (shared/generic contact): {$summary['skipped']}");

        if (! $apply) {
            $this->newLine();
            $this->comment('This was a dry run - nothing was saved. Re-run with --apply to write these changes.');
        }

        return self::SUCCESS;
    }

    /**
     * A "walking customer" style placeholder contact is reused across
     * hundreds of unrelated walk-in sales - it isn't one running balance,
     * just a shared bucket. FIFO-matching a stuck payment against "the
     * contact's oldest due sale" would attribute it to some other stranger's
     * purchase for no real reason. A genuine individual customer with a
     * couple of stuck payments has a handful of sales at most, so a high
     * total count is a reliable signal this contact is a shared bucket.
     */
    private const int SHARED_CONTACT_SALES_THRESHOLD = 20;

    /**
     * @return array{0: string, 1: string} [category, human-readable detail]
     */
    private function reconcileOne(Ledger $ledger, bool $apply, int &$nextInvoiceNumber): array
    {
        $isReceived = $ledger->type === 'payment_received';
        $model = $isReceived ? Sale::class : Purchase::class;
        $label = $isReceived ? 'sale' : 'purchase';

        $totalForContact = $model::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('contact_id', $ledger->contact_id)
            ->count();

        if ($totalForContact > self::SHARED_CONTACT_SALES_THRESHOLD) {
            return ['skipped', "Contact has {$totalForContact} total {$label}s - looks like a shared/generic contact (e.g. walk-in customer), not FIFO-matched. Needs a separate decision."];
        }

        $payAmount = (float) ($isReceived ? $ledger->credit : $ledger->debit);
        $remaining = $payAmount;

        $dueItems = $model::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $ledger->tenant_id)
            ->where('contact_id', $ledger->contact_id)
            ->whereIn('status', ['due', 'partially_paid'])
            ->withSum(['payments' => fn ($q) => $q->where('model_type', $model)], 'amount')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $appliedTo = [];

        foreach ($dueItems as $item) {
            if ($remaining <= 0.01) {
                break;
            }

            $due = (float) $item->grand_total - (float) ($item->payments_sum_amount ?? 0);
            if ($due <= 0.01) {
                continue;
            }

            $amount = min($remaining, $due);

            if ($apply) {
                $transaction = Transaction::query()->create([
                    'date' => $ledger->date,
                    'invoice_number' => ++$nextInvoiceNumber,
                    'full_invoice_number' => ($isReceived ? 'CLT-' : 'SLT-').$nextInvoiceNumber,
                    'model_type' => $model,
                    'model_id' => $item->id,
                    'amount' => $amount,
                    'payment_method_id' => $ledger->payment_method_id,
                    'tenant_id' => $ledger->tenant_id,
                    'note' => 'Reconciled from '.$ledger->full_invoice_number,
                ]);
                $ledger->transactions()->attach($transaction->id);

                $newPaid = (float) ($item->payments_sum_amount ?? 0) + $amount;
                $item->update([
                    'status' => $newPaid >= (float) $item->grand_total ? 'paid' : 'partially_paid',
                ]);
            }

            $appliedTo[] = "{$item->full_invoice_number} (৳".number_format($amount, 2).')';
            $remaining -= $amount;
        }

        if ($appliedTo === []) {
            // Nothing due right now for this contact under this tenant -
            // can't attribute it to a specific invoice anymore, so it's
            // recorded as a pure advance instead, same as the live flow does
            // when a payment has nothing due to apply to.
            if ($apply) {
                AdvanceCredit::query()->create([
                    'tenant_id' => $ledger->tenant_id,
                    'contact_id' => $ledger->contact_id,
                    'date' => $ledger->date,
                    'type' => $isReceived ? 'receivable' : 'payable',
                    'amount' => $payAmount,
                    'note' => 'Reconciled from '.$ledger->full_invoice_number.' - no due '.$label.' found',
                ]);
                $ledger->update([
                    'type' => $isReceived ? 'advance_received' : 'advance_sent',
                    'full_invoice_number' => ($isReceived ? 'ADV-' : 'SADV-').$ledger->invoice_number,
                ]);
            }

            return ['advance', "No due {$label}s for this contact - full ৳".number_format($payAmount, 2).' recorded as advance'];
        }

        $detail = 'Applied to: '.implode(', ', $appliedTo);

        if ($remaining > 0.01) {
            if ($apply) {
                AdvanceCredit::query()->create([
                    'tenant_id' => $ledger->tenant_id,
                    'contact_id' => $ledger->contact_id,
                    'date' => $ledger->date,
                    'type' => $isReceived ? 'receivable' : 'payable',
                    'amount' => $remaining,
                    'note' => 'Reconciled from '.$ledger->full_invoice_number.' - surplus after covering due '.$label.'s',
                ]);
            }
            $detail .= '; surplus ৳'.number_format($remaining, 2).' -> advance';
        }

        return ['matched', $detail];
    }
}
