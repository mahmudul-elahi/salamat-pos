<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_post_views', function (Blueprint $table) {

            $table->id();

            $table->foreignId('post_id')
                ->constrained('blog_posts')
                ->cascadeOnDelete();

            $table->ipAddress('ip')->nullable();

            $table->string('device')->nullable();

            $table->string('browser')->nullable();

            $table->timestamps();

            // Duplicate-view checks ("has this IP already viewed this post?")
            // and per-post view listings both filter on this pair.
            $table->index(['post_id', 'ip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_post_views');
    }
};
