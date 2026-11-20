<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', [
                'dealer',
                'retailer',
                'b2b',
                'consumer',
                'franchise',
            ])->after('status')->default('consumer');

            $table->string('secondary_phone')->nullable()->after('phone');
            $table->string('business_name')->nullable()->after('customer_type');
            $table->string('platform_url')->nullable()->after('business_name');
            $table->string('business_logo')->nullable()->after('platform_url');
            $table->string('order_channel')->nullable()->after('business_logo');
            $table->string('trade_license_number')->nullable()->after('order_channel');
            $table->string('tin_number')->nullable()->after('trade_license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'secondary_phone',
                'business_name',
                'platform_url',
                'business_logo',
                'order_channel',
                'trade_license_number',
                'tin_number',
            ]);
        });
    }
};
