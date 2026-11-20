<?php

use App\Models\Hrm\WorkShift;
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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('in_date');
            $table->foreignIdFor(User::class)->constrained();
            $table->enum('status', ['approved', 'rejected', 'cancel', 'pending'])->default('approved');
            $table->foreignIdFor(WorkShift::class, 'work_shift_id')->constrained('work_shifts');
            $table->enum('behavior', ['early', 'late', 'regular'])->default('regular');
            $table->timestamps();

            $table->unique(['user_id', 'in_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
