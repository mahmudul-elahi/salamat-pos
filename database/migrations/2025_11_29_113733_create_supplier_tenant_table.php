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
        Schema::create('supplier_tenant', function (Blueprint $table) {
            $table->ForeignIdFor(\App\Models\Pos\Supplier\Supplier::class)->constrained()->cascadeOnDelete();
            $table->ForeignIdFor(\App\Models\Tenant\Tenant::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['supplier_id', 'tenant_id']);
            $table->index(['supplier_id', 'tenant_id'], 'tenant_supplier_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_tenant');
    }
};
