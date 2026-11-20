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
        Schema::create('payrun_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('payrun_period', ['monthly', 'weekly']);
            $table->enum('consider_type', ['hour', 'daily_log', 'none']);
            $table->boolean('consider_overtime')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrun_types');
    }
};
