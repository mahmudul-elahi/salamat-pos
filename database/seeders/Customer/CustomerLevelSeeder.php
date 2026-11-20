<?php

namespace Database\Seeders\Customer;

use App\Models\Coupon\CustomerLevel;
use Illuminate\Database\Seeder;

class CustomerLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // gold , silver , bronze , platinum

        $types = ['gold', 'silver', 'bronze', 'platinum'];

        foreach ($types as $type) {
            CustomerLevel::create([
                'name' => $type,
                'min_points' => 0,
                'max_points' => 0,
            ]);
        }
    }
}
