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
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // The local calendar day being corrected (kept separate from
            // in_time/out_time below since converting a local wall-clock time
            // to UTC can shift it onto the adjacent day).
            $table->date('date');
            // Stored as UTC datetimes, like attendance_details.in_time/out_time —
            // converted from the employee's local time at submission time.
            $table->dateTime('in_time')->nullable();
            $table->dateTime('out_time')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attendance_detail_id')
                ->nullable()
                ->constrained('attendance_details')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
