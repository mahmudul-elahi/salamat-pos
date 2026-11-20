<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Marking a serial 'damage' (like the existing 'transfer' status used by
     * stock transfers) removes it from the pool of sellable/transferable
     * stock at its shop without deleting the serial's history.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE tenant_product_serials MODIFY status ENUM('generated','sale','purchase','sale_return','purchase_return','transfer','damage')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tenant_product_serials MODIFY status ENUM('generated','sale','purchase','sale_return','purchase_return','transfer')");
    }
};
