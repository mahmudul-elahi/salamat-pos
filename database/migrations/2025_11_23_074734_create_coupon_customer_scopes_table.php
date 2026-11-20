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
        Schema::create('coupon_customer_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('new_coupon_id')->constrained('new_coupons')->onDelete('cascade');
            $table->foreignId('customer_level_id')->constrained()->onDelete('cascade');
            $table->integer('point_amount');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_customer_scopes');
    }
};
