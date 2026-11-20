<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_owneds', function (Blueprint $table) {
            $table->decimal('booking_amount', 15, 2)->nullable()->after('price');
            $table->integer('purchase_point')->nullable()->after('booking_amount');
        });
    }

    public function down(): void
    {
        Schema::table('pre_owneds', function (Blueprint $table) {
            $table->dropColumn(['booking_amount', 'purchase_point']);
        });
    }
};
