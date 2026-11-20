<?php

use App\Models\Pos\Product\Attribute;
use App\Models\Pos\Product\AttributeValue;
use App\Models\PreOwned\PreOwned;
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
        Schema::create('pre_owned_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PreOwned::class)->constrained('pre_owneds')->cascadeOnDelete();
            $table->foreignIdFor(Attribute::class)->constrained('attributes')->cascadeOnDelete();
            $table->foreignIdFor(AttributeValue::class)->constrained('attribute_values')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_owned_attributes');
    }
};
