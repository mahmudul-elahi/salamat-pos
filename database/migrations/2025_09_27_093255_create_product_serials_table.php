<?php

use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();

            $table->foreignIdFor(\App\Models\Pos\Product\Product::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(ProductVariant::class)->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(\App\Models\Tenant\Tenant::class)
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->enum('status', ['generated', 'purchase', 'sale', 'purchase_return', 'sale_return']);
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id', 'serial_number'], 'product_serial_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};
