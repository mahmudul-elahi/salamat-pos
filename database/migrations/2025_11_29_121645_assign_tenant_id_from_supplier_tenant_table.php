<?php

use App\Models\Pos\Supplier\Supplier;
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
        Schema::table('supplier_tenant', function (Blueprint $table) {
            Supplier::query()
                ->whereNotNull('tenant_id')
                ->get()
                ->each(function ($supplier) {
                    $supplier->tenants()->syncWithoutDetaching([$supplier->tenant_id]);
                });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_tenant', function (Blueprint $table) {
            //
        });
    }
};
