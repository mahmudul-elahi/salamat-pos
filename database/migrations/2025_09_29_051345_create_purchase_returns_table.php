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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->bigIncrements('id');


            $table->foreignIdFor(\App\Models\Pos\Supplier\Supplier::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(\App\Models\Tenant\Tenant::class)
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->date('date')->index();
            $table->unsignedBigInteger('invoice_number');
            $table->string('full_invoice_number', 50)->unique();

            $table->decimal('sub_total', 15, 2);
            $table->enum('discount_type', ['fixed', 'percent'])->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('order_tax', 5, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->enum('status', ['paid', 'partially_paid', 'due'])->default('due');

            $table->timestamps();
            $table->softDeletes();


            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
