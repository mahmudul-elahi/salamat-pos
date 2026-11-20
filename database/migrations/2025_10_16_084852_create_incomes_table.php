<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191)->index();
            $table->date('date')->index();

            $table->foreignIdFor(\App\Models\Pos\Income\IncomeCategory::class)
                ->constrained('income_categories')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2)->index();
            $table->text('note')->nullable();
            $table->enum('status', ['due', 'partially_paid', 'paid'])->default('due');
            $table->foreignIdFor(\App\Models\Tenant\Tenant::class, 'tenant_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'income_category_id'], 'incomes_tenant_category_date_idx');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
