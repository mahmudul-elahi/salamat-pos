<?php

use App\Models\Tenant\Tenant;
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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            // Multi-tenant
            $table->foreignIdFor(Tenant::class)
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Product + Variant
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            // Stock tracking
            $table->integer('quantity')->default(0);

            // For traceability
            $table->timestamps();

            $table->softDeletes();
            $table->index(['tenant_id', 'product_id', 'product_variant_id'], 'index_stock');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
