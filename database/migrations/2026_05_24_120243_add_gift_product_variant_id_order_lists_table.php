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
        Schema::table('order_lists', function (Blueprint $table) {
            $table->foreignId('gift_product_variant_id')
                ->nullable()
                ->constrained('gift_product_variants')
                ->after('pre_owned_id');

            $table->foreignId('product_care_id')
                ->nullable()
                ->constrained('product_cares')
                ->after('gift_product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_product_variant_id');
            $table->dropConstrainedForeignId('product_care_id');
        });
    }
};
