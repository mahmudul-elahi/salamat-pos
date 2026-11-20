<?php

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
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->string('recipient_district')->nullable()->after('recipient_address');
            $table->string('recipient_thana')->nullable()->after('recipient_district');
            $table->boolean('is_exchange')->default(false)->after('item_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->dropColumn(['recipient_district', 'recipient_thana', 'is_exchange']);
        });
    }
};
