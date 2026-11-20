<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profit_loss_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\Pos\Sale\Sale::class)->index()->constrained('sales');
            $table->foreignIdFor(App\Models\Tenant\Tenant::class)->index()->constrained('tenants');
            $table->foreignIdFor(\App\Models\Pos\Product\Product::class)->index()->constrained('products');
            $table->foreignIdFor(\App\Models\Pos\Product\ProductVariant::class)->nullable()->index()->constrained('product_variants');
            $table->date('sale_date')->index();
            $table->string('full_invoice_number')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('sale_unit_price', 15, 2)->default(0);
            $table->decimal('purchase_unit_price', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percent'])->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_sale_price', 15, 2)->default(0);
            $table->decimal('total_purchase_price', 15, 2)->default(0);
            $table->decimal('profit_loss', 15, 2)->default(0);
            $table->timestamps();

            // One row per sale_item line
            //  $table->unique(['sale_id', 'product_id', 'product_variant_id'], 'uq_sale_product_variant');
            $table->index(['tenant_id', 'sale_date']);
            $table->index(['product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_loss_summaries');
    }
};
