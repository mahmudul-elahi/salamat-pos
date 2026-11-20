<?php

use App\Models\Hrm\Asset;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Asset::class, 'asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->date('returned_date')->nullable();
            $table->string('condition')->nullable();
            $table->text('return_note')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'returned_date'], 'asset_assignment_current_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
