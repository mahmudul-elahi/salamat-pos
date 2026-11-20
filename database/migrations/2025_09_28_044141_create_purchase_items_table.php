<?php

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Purchase\Purchase;
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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignIdFor(Product::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(ProductVariant::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(Purchase::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('quantity')->unsigned();
            $table->decimal('unit_price', 15, 2);
            $table->string('serial_number')->unique();
            $table->string('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_id', 'product_id'], 'purchase_product_index');
            $table->index(['product_variant_id'], 'purchase_items_variant_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
