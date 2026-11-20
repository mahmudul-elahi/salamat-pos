<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_serial_id')->nullable()->after('product_variant_id');
            $table->foreign('product_serial_id')->references('id')->on('product_serials')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['product_serial_id']);
            $table->dropColumn('product_serial_id');
        });
    }
};
