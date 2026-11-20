<?php

namespace Database\Seeders\Drop;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PreviousMigrationTableDrop extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $migrations = [
            '2023_09_21_081349_create_suppliers_table',
            '2025_09_16_144800_alter_suppliers_table',
            '2023_11_06_133316_create_purchases_table',
            '2025_09_17_174509_create_purchase_items_table',
            '2025_09_20_213822_create_new_purchase_table',
        ];

        DB::table('migrations')
            ->whereIn('migration', $migrations)
            ->delete();

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('new_purchases');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        Log::info('Previous migrations dropped successfully');
    }
}
