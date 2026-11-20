<?php

use App\Models\Ecommerce\Outlet\Outlet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlet_images', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Outlet::class)->constrained()->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->enum('type', ['banner', 'gallery', 'thumbnail'])->default('gallery');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_images');
    }
};
