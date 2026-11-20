<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets editors decide which posts lead the storefront's Recent Posts block
     * and the first page of All Posts, instead of both being purely newest-first.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_popular');
            $table->unsignedInteger('pinned_position')->default(0)->after('is_pinned');
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['is_pinned']);
            $table->dropColumn('is_pinned');
            $table->dropColumn('pinned_position');
        });
    }
};
