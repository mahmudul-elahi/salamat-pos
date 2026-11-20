<?php

use App\Models\Pos\Expense\ExpenseCategory;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->string('title', 191)->index();
            $table->date('date')->index();

            $table->foreignIdFor(ExpenseCategory::class, 'expense_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2)->index();
            $table->text('note')->nullable();
            $table->enum('status', ['due', 'partially_paid', 'paid'])->default('due');
            $table->foreignIdFor(Tenant::class, 'tenant_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'expense_category_id', 'date'], 'expenses_tenant_category_date_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
