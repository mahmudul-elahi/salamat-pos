<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo', function (Blueprint $table) {

            // 1️⃣ tag column: varchar → text
            $table->text('tags')->nullable()->change();

            // 2️⃣ long_description add
            // if (!Schema::hasColumn('seo', 'long_description')) {
            //     $table->longText('long_description')
            //           ->nullable()
            //           ->after('tags');
            // }
        });
    }

    public function down(): void
    {
        Schema::table('seo', function (Blueprint $table) {

            // rollback tag
            $table->string('tags', 255)->nullable()->change();

            // rollback long_description
            // if (Schema::hasColumn('seo', 'long_description')) {
            //     $table->dropColumn('long_description');
            // }
        });
    }
};
