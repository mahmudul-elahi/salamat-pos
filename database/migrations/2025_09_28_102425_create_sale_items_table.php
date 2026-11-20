<?php

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Sale\Sale;
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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignIdFor(Sale::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(Product::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(ProductVariant::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('quantity')->unsigned();
            $table->decimal('unit_price', 15, 2);

            $table->string('serial_number');
            $table->enum('discount_type', ['fixed', 'percent'])->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->string('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_id', 'product_id'], 'sale_product_index');
            $table->index(['product_variant_id'], 'sale_items_variant_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
