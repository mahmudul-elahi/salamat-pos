<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Filters\Pos\Shop\ShopFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Transaction\Transaction;
use Illuminate\Support\Facades\DB;

class DailyPayReceivableController extends Controller
{
    public function receivedAble()
    {
        $search = request('search');
        $shopFilter = new ShopFilter();

        // Subquery for total sale amount
        $totalSaleQuery = Sale::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(grand_total)')
            ->whereColumn('contact_id', 'contacts.id');

        // Subquery for total paid for sales
        $totalPaidSaleQuery = Transaction::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(amount)')
            ->whereHasMorph(
                'model',
                [Sale::class],
                fn($q) =>
                $q->whereColumn('contact_id', 'contacts.id')
            );

        // Subquery for total paid for sale returns
        $totalPaidReturnQuery = SaleReturn::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(grand_total)')
            ->whereColumn('contact_id', 'contacts.id');

        return Contact::query()
            ->whereHas('sales')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->selectRaw('
            contacts.*,
            COALESCE((' . $totalSaleQuery->toSql() . '), 0)
            - COALESCE((' . $totalPaidSaleQuery->toSql() . '), 0)
            - COALESCE((' . $totalPaidReturnQuery->toSql() . '), 0)
            AS total_receivable
        ')
            ->mergeBindings($totalSaleQuery->getQuery())
            ->mergeBindings($totalPaidSaleQuery->getQuery())
            ->mergeBindings($totalPaidReturnQuery->getQuery())
            ->orderByDesc('id')
            ->having('total_receivable', '>', 0)
            ->paginate(request('per_page', 10));
    }

    public function payAble()
    {
        $search = request('search');
        $shopFilter = new ShopFilter();

        // Subquery for total purchase amount
        $totalPurchaseQuery = Purchase::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(grand_total)')
            ->whereColumn('contact_id', 'contacts.id');

        // Subquery for total paid for purchases
        $totalPaidPurchaseQuery = PurchaseReturn::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(grand_total)')
            ->whereColumn('contact_id', 'contacts.id');

        // Subquery for total paid for returns
        $totalPaidReturnQuery = Transaction::query()
            ->filters($shopFilter)
            ->selectRaw('SUM(amount)')
            ->whereHasMorph(
                'model',
                [PurchaseReturn::class],
                fn($q) =>
                $q->whereColumn('contact_id', 'contacts.id')
            );

        return Contact::query()
            ->whereHas('purchases')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->selectRaw('
            contacts.*,
            COALESCE((' . $totalPurchaseQuery->toSql() . '), 0)
            - COALESCE((' . $totalPaidPurchaseQuery->toSql() . '), 0)
            - COALESCE((' . $totalPaidReturnQuery->toSql() . '), 0)
            AS total_payable
        ')
            ->mergeBindings($totalPurchaseQuery->getQuery())
            ->mergeBindings($totalPaidPurchaseQuery->getQuery())
            ->mergeBindings($totalPaidReturnQuery->getQuery())
            ->orderByDesc('id')
            ->having('total_payable', '>', 0)
            ->paginate(request('per_page', 10));
    }

    public function contactBalanceSheet()
    {
        $search = request('search');
        $perPage = request('per_page', 10);
        $shopFilter = new ShopFilter();

        // 'receivable' = they owe us (positive balance), 'payable' = we owe
        // them (negative). Anything else means no direction filter.
        $balanceType = request('balance_type');

        $tenantId = auth()->user()->isAppAdmin() ? request('shop') : tenant()->id;

        $baseQuery = Contact::query()
            ->where(function ($q) {
                $q->whereHas('sales')->orWhereHas('purchases');
            })
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->select(
                'contacts.id',
                'contacts.name',
                'contacts.business_name',
                'contacts.phone',
                'contacts.email',
                // The name column renders an avatar; without this it always
                // fell back to initials.
                'contacts.profile_picture'
            )
            ->selectSub(
                Sale::query()->filters($shopFilter)->selectRaw('COALESCE(SUM(grand_total), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_sale'
            )
            ->selectSub(
                PurchaseReturn::query()->filters($shopFilter)->selectRaw('COALESCE(SUM(grand_total), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_purchase_return'
            )
            ->selectSub(
                SaleReturn::query()->filters($shopFilter)->selectRaw('COALESCE(SUM(grand_total), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_sale_return'
            )
            ->selectSub(
                Purchase::query()->filters($shopFilter)->selectRaw('COALESCE(SUM(grand_total), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_purchase'
            )
            ->selectSub(
                Ledger::query()
                    ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                    ->filters($shopFilter)->selectRaw('COALESCE(SUM(debit), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_ledger_debit'
            )
            ->selectSub(
                Ledger::query()
                    ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                    ->filters($shopFilter)->selectRaw('COALESCE(SUM(credit), 0)')->whereColumn('contact_id', 'contacts.id'),
                'total_ledger_credit'
            );

        $finalQuery = DB::query()
            ->fromSub($baseQuery, 't')
            ->selectRaw("
        t.*,
        (COALESCE(t.total_sale, 0) + COALESCE(t.total_purchase_return, 0) + COALESCE(t.total_ledger_debit, 0)) AS total_debit,
        (COALESCE(t.total_sale_return, 0) + COALESCE(t.total_purchase, 0) + COALESCE(t.total_ledger_credit, 0)) AS total_credit,
        (
            (COALESCE(t.total_sale, 0) + COALESCE(t.total_purchase_return, 0) + COALESCE(t.total_ledger_debit, 0))
            -
            (COALESCE(t.total_sale_return, 0) + COALESCE(t.total_purchase, 0) + COALESCE(t.total_ledger_credit, 0))
        ) AS balance
    ")
            // A zero balance means the account is settled — nothing to chase or
            // pay — so those rows never belong on this sheet.
            ->havingRaw('balance != 0')
            ->when($balanceType === 'receivable', fn($query) => $query->havingRaw('balance > 0'))
            ->when($balanceType === 'payable', fn($query) => $query->havingRaw('balance < 0'))
            ->orderByDesc('t.id');

        $grandTotal = DB::query()
            ->fromSub($finalQuery, 'summary')
            ->selectRaw("
        COALESCE(SUM(total_debit), 0)  AS total_debit,
        COALESCE(SUM(total_credit), 0) AS total_credit,
        COALESCE(SUM(balance), 0)      AS balance
    ")
            ->first();

        $paginated = $finalQuery->paginate($perPage);

        return [
            ...$paginated->toArray(),
            'grand_total' => [
                'total_debit'  => $grandTotal->total_debit,
                'total_credit' => $grandTotal->total_credit,
                'balance'      => $grandTotal->balance,
            ],
        ];
    }
}
