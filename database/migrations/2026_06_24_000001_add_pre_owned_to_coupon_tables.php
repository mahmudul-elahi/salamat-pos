<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add pre_owned to new_coupons.type enum
        DB::statement("ALTER TABLE new_coupons MODIFY COLUMN type ENUM('product','category','brand','customer','pre_owned') NOT NULL");

        // Add pre_owned_id column and pre_owned to scope_type enum
        Schema::table('coupon_product_scopes', function (Blueprint $table) {
            $table->foreignId('pre_owned_id')->nullable()->after('brand_id')->constrained('pre_owneds')->nullOnDelete();
        });

        DB::statement("ALTER TABLE coupon_product_scopes MODIFY COLUMN scope_type ENUM('all','category','product','brand','pre_owned') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE new_coupons MODIFY COLUMN type ENUM('product','category','brand','customer') NOT NULL");
        DB::statement("ALTER TABLE coupon_product_scopes MODIFY COLUMN scope_type ENUM('all','category','product','brand') NOT NULL");

        Schema::table('coupon_product_scopes', function (Blueprint $table) {
            $table->dropForeign(['pre_owned_id']);
            $table->dropColumn('pre_owned_id');
        });
    }
};
