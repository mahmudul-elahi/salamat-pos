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
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('ledger_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('ledgers')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->after('ledger_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['ledger_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['contact_id', 'ledger_id', 'created_by']);
        });
    }
};
