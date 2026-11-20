<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_confirmations', function (Blueprint $table) {
            $table->decimal('shipping_charge', 15, 2)->default(0)->after('selling_price');

            // Set the first time this confirmation is confirmed, when a real
            // Order is created from it - links back to the order it spawned,
            // and its presence is what stops a re-confirm from creating another.
            $table->foreignId('order_id')->nullable()->after('confirmation_date')
                ->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_confirmations', function (Blueprint $table) {
            $table->dropColumn('shipping_charge');
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
