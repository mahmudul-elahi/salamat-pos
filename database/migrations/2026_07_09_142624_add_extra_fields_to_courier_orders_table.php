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
            $table->string('recipient_alternative_phone')->nullable()->after('recipient_phone');
            $table->string('recipient_email')->nullable()->after('recipient_address');
            $table->string('item_description')->nullable()->after('special_instruction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->dropColumn(['recipient_alternative_phone', 'recipient_email', 'item_description']);
        });
    }
};
