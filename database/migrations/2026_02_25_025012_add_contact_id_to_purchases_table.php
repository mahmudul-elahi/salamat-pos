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
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('id') // optional
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->index(['contact_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->index(['contact_id']);
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->index(['contact_id']);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->index(['contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};
