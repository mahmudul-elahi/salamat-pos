<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Call outcomes ("Sale Done", "Follow Up", "Stock Out", …). Kept in their
     * own table rather than an enum so the sales team can add, rename and
     * recolour them without a deploy.
     */
    public function up(): void
    {
        Schema::create('telesale_results', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Bootstrap contextual colour used for the badge in the list.
            $table->string('color', 20)->default('secondary');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telesale_results');
    }
};
