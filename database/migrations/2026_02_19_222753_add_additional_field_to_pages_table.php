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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('primary_logo')->nullable()->after('tenant_id');
            $table->string('secondary_logo')->nullable()->after('primary_logo');
            $table->string('shop_banner')->nullable()->after('secondary_logo');
            $table->string('info_banner')->nullable()->after('shop_banner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('primary_logo');
            $table->dropColumn('secondary_logo');
            $table->dropColumn('shop_banner');
            $table->dropColumn('info_banner');
        });
    }
};
