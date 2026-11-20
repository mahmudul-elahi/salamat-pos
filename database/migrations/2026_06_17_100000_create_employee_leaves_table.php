<?php

use App\Models\Hrm\LeaveType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'employee_id')->constrained();
            $table->foreignIdFor(LeaveType::class)->constrained();
            $table->float('day');
            $table->boolean('is_updated')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
    }
};
