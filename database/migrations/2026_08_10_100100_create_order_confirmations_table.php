<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            // Set once the customer has been called and the order confirmed —
            // null means it is still waiting on that call.
            $table->date('confirmation_date')->nullable();

            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('customer_name');
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();

            // Where the order came in from — Facebook, WhatsApp, TikTok, …
            $table->string('source_media')->nullable();
            $table->string('order_type')->default('online'); // online | offline

            // Either a catalogue product or, when we don't stock it, free text.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('custom_product')->nullable();

            $table->foreignId('order_person_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('advance_media_id')->nullable()->constrained('order_confirmation_options')->nullOnDelete();
            $table->decimal('advance_amount', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('cod', 15, 2)->default(0);
            $table->text('note')->nullable();

            // ── Delivery leg ────────────────────────────────────────────────
            $table->foreignId('delivery_status_id')->nullable()->constrained('order_confirmation_options')->nullOnDelete();
            $table->string('invoice_no')->nullable();
            $table->foreignId('delivery_by_id')->nullable()->constrained('order_confirmation_options')->nullOnDelete();
            $table->string('track_url', 500)->nullable();
            $table->text('delivery_note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('mobile');
            $table->index('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmations');
    }
};
