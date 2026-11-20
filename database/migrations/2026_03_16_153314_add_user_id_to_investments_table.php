<?php

use App\Models\User;
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
        Schema::table('investments', function (Blueprint $table) {
            $table->foreignIdFor(User::class)
                ->nullable()
                ->after('date')
                ->constrained();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignIdFor(User::class)
                ->nullable()
                ->after('date')
                ->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
