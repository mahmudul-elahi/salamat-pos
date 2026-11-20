<?php

use App\Models\Pos\Product\ProductSerial;
use App\Models\Tenant\Tenant;
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
        Schema::create('tenant_product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tenant::class)->constrained();
            $table->foreignIdFor(ProductSerial::class)->constrained()->cascadeOnDelete();
            $table->enum('status', ['generated', 'sale', 'purchase', 'sale_return', 'purchase_return'])->default('generated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_product_serials');
    }
};
