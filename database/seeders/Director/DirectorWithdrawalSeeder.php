<?php

namespace Database\Seeders\Director;

use App\Models\Pos\Income\Income;
use App\Models\Pos\Income\IncomeCategory;
use Illuminate\Database\Seeder;

class DirectorWithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incomes = Income::query()
            ->whereNull('user_id')
            ->get();

        foreach ($incomes as $income) {
            $incomeCategory = IncomeCategory::query()
                ->where('tenant_id', $income->tenant_id)
                ->first();

            $income->update([
                'user_id' => $incomeCategory->director_id,
                'income_category_id' => $incomeCategory->id,
            ]);
        }
    }
}
