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
        Schema::create('party_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->foreignId('tenant_id')->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->timestamps();


            $table->unique(['customer_id', 'supplier_id', 'tenant_id'], 'party_tenant_unique');

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_mappings');
    }
};
