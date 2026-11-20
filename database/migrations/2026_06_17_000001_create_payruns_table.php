<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adapted from the requested schema: the `status_id` foreign key (to a
     * generic Status model that doesn't exist in this app) was replaced with
     * a plain `status` string column — values: processing, generated,
     * failed, sent (see App\Models\Hrm\Payrun).
     *
     * `start_date`/`end_date` were added as real columns (rather than only
     * inside `data`) so the payrun list/report queries can filter/sort on
     * them directly instead of unpacking JSON.
     */
    public function up(): void
    {
        Schema::create('payruns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('batch_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_employees')->default(0);
            $table->json('data')->nullable();
            $table->string('status')->default('processing');
            $table->enum('type', ['customized', 'settings', 'auto', 'manual'])->default('settings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payruns');
    }
};
