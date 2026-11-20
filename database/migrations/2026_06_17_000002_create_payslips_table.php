<?php

use App\Models\Hrm\Department;
use App\Models\Hrm\Payrun;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adapted: `status_id` (FK to a non-existent generic Status model)
     * replaced with a plain `status` string column — values: generated, sent
     * (see App\Models\Hrm\Payslip). Also scoped the model references to the
     * classes that actually exist in this app (App\Models\Hrm\Department,
     * App\Models\Hrm\Payrun) instead of the nested namespaces in the
     * original draft.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Department::class, 'department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->foreignIdFor(Payrun::class)->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('basic_salary', 14, 2);
            $table->decimal('net_salary', 14, 2);
            $table->string('period');
            $table->boolean('consider_overtime')->default(true);
            $table->enum('consider_type', ['hour', 'daily_log', 'none']);
            $table->string('status')->default('generated');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['payrun_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
