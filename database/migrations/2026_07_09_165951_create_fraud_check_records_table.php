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
        Schema::create('fraud_check_records', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('provider');
            $table->unsignedInteger('success')->default(0);
            $table->unsignedInteger('cancel')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->decimal('success_ratio', 5, 2)->default(0);
            $table->string('source')->default('live');
            $table->json('raw_response')->nullable();
            $table->dateTime('checked_at');
            $table->timestamps();

            $table->unique(['phone', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_check_records');
    }
};
