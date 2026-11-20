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
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->enum('gender', ['male', 'female', 'others'])->nullable();
            $table->string('employee_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('phone_country')->nullable();
            $table->string('married_status')->nullable();
            $table->string('religion')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('nid_no')->nullable();
            $table->text('address')->nullable();
            $table->enum('blood_type', ['a+', 'a-', 'b+', 'b-', 'o+', 'o-', 'ab+', 'ab-'])->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
