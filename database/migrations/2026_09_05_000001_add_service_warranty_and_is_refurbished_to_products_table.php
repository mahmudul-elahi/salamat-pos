<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('service_warranty')->nullable()->after('warranty_text');
            $table->boolean('is_refurbished')->default(false)->after('is_unofficial');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['service_warranty', 'is_refurbished']);
        });
    }
};
