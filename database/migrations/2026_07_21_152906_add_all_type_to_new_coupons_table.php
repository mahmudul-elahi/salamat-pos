<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE new_coupons MODIFY COLUMN type ENUM('product','category','brand','customer','pre_owned','all') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE new_coupons MODIFY COLUMN type ENUM('product','category','brand','customer','pre_owned') NOT NULL");
    }
};
