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
        Schema::table('pre_owneds', function (Blueprint $table) {

            $table->enum('discount_type', ['fixed', 'percent'])
                ->nullable()
                ->after('battery_health');

            $table->decimal('discount_amount', 15, 2)
                ->default(0)
                ->after('discount_type');

            $table->boolean('box')->default(false)->after('discount_type');

            $table->string('charging_time')->nullable()->after('box');

            $table->string('official_service_warranty')->nullable()->after('charging_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_owneds', function (Blueprint $table) {
            //
        });
    }
};
