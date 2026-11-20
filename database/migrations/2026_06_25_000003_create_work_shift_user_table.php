<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shift_user', function (Blueprint $table) {
            $table->foreignId('work_shift_id')->constrained('work_shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['work_shift_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shift_user');
    }
};
