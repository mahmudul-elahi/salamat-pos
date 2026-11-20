<?php

use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\Tenant\Tenant;
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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->date('date')->index();

            $table->foreignIdFor(InvestmentCategory::class)
                ->constrained('investment_categories')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2)->index();
            $table->text('note')->nullable();
            $table->foreignIdFor(Tenant::class, 'tenant_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->index(['tenant_id', 'investment_category_id'], 'investments_tenant_category_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
