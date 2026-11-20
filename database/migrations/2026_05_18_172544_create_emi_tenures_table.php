<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emi_tenures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emi_bank_id')->constrained()->onDelete('cascade');
            $table->integer('months');
            $table->decimal('charge_rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emi_tenures');
    }
};
