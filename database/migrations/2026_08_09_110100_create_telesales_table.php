<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telesales', function (Blueprint $table) {
            $table->id();
            $table->date('date');

            // Matched against contacts by phone at entry time. contact_id is the
            // match itself; is_existing_customer is the snapshot of whether the
            // caller was already in the system *then*, which is what the report
            // needs — adding the customer later must not rewrite history.
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->boolean('is_existing_customer')->default(false);

            $table->string('customer_name')->nullable();
            $table->string('customer_number');

            // Either a catalogue product or, when we don't stock it, free text.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('custom_product')->nullable();

            $table->string('media')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('telesale_result_id')->nullable()->constrained('telesale_results')->nullOnDelete();
            $table->foreignId('salesman_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_number');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telesales');
    }
};
