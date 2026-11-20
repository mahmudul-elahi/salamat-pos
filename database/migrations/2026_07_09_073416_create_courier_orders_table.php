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
        Schema::create('courier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('provider')->default('pathao');
            $table->string('store_id')->nullable();
            $table->string('store_name')->nullable();

            $table->string('consignment_id')->nullable();
            $table->string('merchant_order_id')->nullable();

            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('recipient_address');
            $table->string('recipient_city_id')->nullable();
            $table->string('recipient_city_name')->nullable();
            $table->string('recipient_zone_id')->nullable();
            $table->string('recipient_zone_name')->nullable();
            $table->string('recipient_area_id')->nullable();
            $table->string('recipient_area_name')->nullable();

            $table->unsignedTinyInteger('item_type')->default(2);
            $table->unsignedTinyInteger('delivery_type')->default(48);
            $table->unsignedInteger('item_quantity')->default(1);
            $table->decimal('item_weight', 8, 2)->default(0.5);
            $table->decimal('amount_to_collect', 12, 2)->default(0);
            $table->text('special_instruction')->nullable();

            $table->string('status')->default('pending');
            $table->string('payment_status')->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->nullable();

            $table->json('raw_response')->nullable();
            $table->dateTime('courier_created_at')->nullable();
            $table->dateTime('courier_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_orders');
    }
};
