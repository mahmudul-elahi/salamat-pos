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
        Schema::create('purchase_return_items', function (Blueprint $table) {
            
            $table->foreignIdFor(\App\Models\Pos\PurchaseReturn\PurchaseReturn::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(\App\Models\Pos\Purchase\Purchase::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(\App\Models\Pos\Product\Product::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(\App\Models\Pos\Product\ProductVariant::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('quantity')->unsigned();
            $table->decimal('unit_price', 15, 2);
            $table->string('serial_number')->unique();
            $table->string('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_return_id', 'product_id'], 'purchase_return_product_index');
            $table->index(['product_variant_id'], 'variant_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
