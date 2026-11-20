<?php

use App\Models\Hrm\Attendance;
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
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_details_id')->nullable()->constrained('attendance_details')->cascadeOnDelete();
            $table->dateTime('in_time');
            $table->dateTime('out_time')->nullable();
            $table->foreignIdFor(Attendance::class)->constrained();
            $table->foreignIdFor(User::class, 'review_by')
                ->nullable()->constrained('users');
            $table->foreignIdFor(User::class, 'added_by')
                ->nullable()
                ->constrained('users', 'id');
            $table->text('in_ip_data')->nullable();
            $table->text('out_ip_data')->nullable();
            $table->text('in_note')->nullable();
            $table->text('out_note')->nullable();
            $table->boolean('work_from_home')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
