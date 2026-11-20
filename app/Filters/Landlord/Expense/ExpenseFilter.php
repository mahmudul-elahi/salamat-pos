<?php

namespace App\Filters\Landlord\Expense;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExpenseFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($builder) use ($search) {
            $table = $builder->getModel()->getTable();
            $builder->where($table.'.title', 'LIKE', "%$search%")
                ->orWhere($table.'.id', 'LIKE', "%$search%")
                ->orWhere($table.'.amount', 'LIKE', "%$search%");

            // The invoice number shown on screen ("EXP-{id}") is synthetic
            // (built in the controller, not a real column), so searching
            // for it — e.g. "EXP-42" — never matched anything since only
            // the bare numeric id (not the prefixed string) was compared
            // above. Strip any non-digit prefix and match against id too.
            $numeric = preg_replace('/^\D+/', '', trim($search));
            if ($numeric !== '' && is_numeric($numeric)) {
                $builder->orWhere($table.'.id', (int) $numeric);
            }
        });
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null)
    {
        $table = $this->builder->getModel()->getTable();

        if (empty($range)) {
            return $this->builder;
        }

        // Handle JSON string input from query params
        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (! is_array($range) || count($range) < 2) {
            return $this->builder->whereDate($table.'.date', Carbon::today());
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            return $this->builder->whereBetween($table.'.date', [$start, $end]);
        } catch (\Exception) {
            throw new GeneralException('Invalid your date range');
        }
    }

    public function category($ids = null): void
    {
        $category = explode(',', $ids);

        $this->builder->when($ids, fn (Builder $query) => $query->whereHas('category', fn (Builder $query) => $query->whereIn('expense_category_id', $category)));
    }

    public function shop($shopId = null): void
    {
        if (! $shopId) {
            return;
        }
        $this->builder->when($shopId, fn ($query) => $query->where('tenant_id', $shopId));
    }

    public function warehouse($shopId = null): void
    {
        if (! $shopId) {
            return;
        }
        $this->builder->when($shopId, fn ($query) => $query->where('tenant_id', $shopId));
    }

    // Used when this filter is applied to Income (incomes.user_id = the director who
    // received/withdrew) via IncomeController, WithdrawalReportController, DirectorController.
    // Not applicable to Expense — expenses has no user_id/director column.
    public function director($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('user_id', $ids);
    }

    public function paymentMethod($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('payment_method_id', $ids);
    }

    public function createdBy($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('created_by', $ids);
    }
}
