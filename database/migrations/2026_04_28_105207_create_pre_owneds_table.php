<?php

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
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
        Schema::create('pre_owneds', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('serial_number')->unique();
            $table->string('battery_health')->nullable();
            $table->string('thumbnail_image');
            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_owneds');
    }
};
