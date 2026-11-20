<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An order can carry several products, so the single product columns on
     * order_confirmations move out to their own rows. Safe to drop outright —
     * the feature is new and the table holds no data yet.
     */
    public function up(): void
    {
        Schema::create('order_confirmation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_confirmation_id')->constrained()->cascadeOnDelete();

            // Either a catalogue product or, when we don't stock it, free text.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('custom_product')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('order_confirmations', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'custom_product']);
        });
    }

    public function down(): void
    {
        Schema::table('order_confirmations', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_type')->constrained('products')->nullOnDelete();
            $table->string('custom_product')->nullable()->after('product_id');
        });

        Schema::dropIfExists('order_confirmation_items');
    }
};
