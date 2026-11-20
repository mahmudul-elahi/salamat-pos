<?php

use App\Models\Hrm\Beneficiary;
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
        Schema::create('beneficiary_values', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->unsignedInteger('model_id');
            $table->foreignIdFor(Beneficiary::class)->constrained('beneficiaries');
            $table->decimal('amount');
            $table->boolean('is_percentage')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_values');
    }
};
