<?php

use App\Models\Pos\Supplier\Supplier;
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
        Schema::create('supplier_tenant', function (Blueprint $table) {
            $table->ForeignIdFor(Supplier::class)->constrained()->cascadeOnDelete();
            $table->ForeignIdFor(Tenant::class)
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
