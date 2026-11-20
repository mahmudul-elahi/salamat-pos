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
        Schema::table('areas', function (Blueprint $table) {
            // Null means "not configured for this area" — the storefront
            // checkout falls back to the Dhaka/outside-Dhaka district charge
            // in that case. 0 means an explicit free-delivery override.
            $table->decimal('shipping_charge', 10, 2)->nullable()->after('postcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('shipping_charge');
        });
    }
};
