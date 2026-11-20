<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {

            // 1. Drop all foreign keys using these columns
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['area_id']);
            $table->dropForeign(['new_district_id']);

            // 2. Now drop composite index
            $table->dropIndex('suppliers_location_index');

            // 3. Finally drop tenant_id
            $table->dropColumn('tenant_id');

            $table->index(
                ['country_id', 'area_id', 'new_district_id'],
                'suppliers_location_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {

            $table->foreignId('tenant_id')->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            // restore other fks
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
            $table->foreign('new_district_id')->references('id')->on('new_districts')->cascadeOnDelete();

            $table->index(
                ['tenant_id', 'country_id', 'area_id', 'new_district_id'],
                'suppliers_location_index'
            );
        });
    }
};
