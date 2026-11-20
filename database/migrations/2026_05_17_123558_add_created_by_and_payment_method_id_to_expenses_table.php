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
        Schema::table('expenses', function (Blueprint $table) {

            $table->foreignId('created_by')
                ->nullable()
                ->after('tenant_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('created_by')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['created_by', 'payment_method_id']);
        });
    }
};
