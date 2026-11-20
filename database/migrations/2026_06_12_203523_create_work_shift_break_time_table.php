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
        Schema::create('work_shift_break_time', function (Blueprint $table) {
            $table->foreignId('work_shift_id')->constrained('work_shifts')->cascadeOnDelete();
            $table->foreignId('break_time_id')->constrained('break_times')->cascadeOnDelete();
            $table->primary(['work_shift_id', 'break_time_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shift_break_time');
    }
};
