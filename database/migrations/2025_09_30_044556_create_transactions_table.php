<?php

use App\Models\Tenant\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->integer('invoice_number')->unique();
            $table->string('full_invoice_number')->unique();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->decimal('amount', 15, 2);
            $table->foreignIdFor(\App\Models\Pos\PaymentMethod\PaymentMethod::class)->constrained();
            $table->foreignIdFor(Tenant::class)->nullable()->constrained();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id'], 'transactions_model_index');
            $table->index(['tenant_id', 'date'], 'transactions_tenant_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
