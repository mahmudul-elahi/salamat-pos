<?php

namespace Database\Seeders\Director;

use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Investment\InvestmentCategory;
use Illuminate\Database\Seeder;

class DirectorDepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deposits = Investment::query()
            ->whereNull('user_id')
            ->get();

        foreach ($deposits as $deposit) {
            $investmentCategory = InvestmentCategory::query()
                ->where('tenant_id', $deposit->tenant_id)
                ->first();

            $deposit->update([
                'user_id' => $investmentCategory->director_id,
                'investment_category_id' => $investmentCategory->id,
            ]);
        }
    }
}
