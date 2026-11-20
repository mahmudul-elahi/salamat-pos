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
        Schema::create('blog_posts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained('blog_categories')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users');

            $table->string('title');

            $table->string('slug')->unique();

            $table->string('excerpt', 500)->nullable();

            $table->longText('content');

            // Images
            $table->string('thumbnail');
            $table->string('banner')->nullable();

            // Reading
            $table->unsignedSmallInteger('reading_time')->default(5);

            // Counters
            $table->unsignedInteger('views')->default(0);

            $table->unsignedInteger('shares')->default(0);

            // Status

            $table->boolean('is_featured')->default(false)->index();

            $table->boolean('is_trending')->default(false)->index();

            $table->boolean('is_popular')->default(false)->index();

            $table->boolean('is_published')->default(false);

            $table->timestamp('published_at')->nullable();

            // SEO

            $table->string('meta_title')->nullable();

            $table->text('meta_description')->nullable();

            $table->string('meta_keywords')->nullable();

            $table->timestamps();

            $table->softDeletes();

            // Storefront's main feed query: published posts ordered by date.
            $table->index(['is_published', 'published_at']);

            // Category-filtered published feed.
            $table->index(['category_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
