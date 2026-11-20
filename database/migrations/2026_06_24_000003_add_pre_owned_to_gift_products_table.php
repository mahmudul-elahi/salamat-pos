<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('pre_owned_id')->nullable()->after('product_id')
                ->constrained('pre_owneds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gift_products', function (Blueprint $table) {
            $table->dropForeign(['pre_owned_id']);
            $table->dropColumn('pre_owned_id');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
