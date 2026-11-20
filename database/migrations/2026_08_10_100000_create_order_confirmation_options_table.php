<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The three badge dropdowns on the order confirmation sheet — advance
     * media ("Bkash 22", "SSL", "EMI"), delivery status and courier — all
     * behave the same way: a named, coloured, reorderable list the team edits
     * themselves. One table with a `type` keeps that to a single CRUD screen
     * instead of three near-identical ones.
     */
    public function up(): void
    {
        Schema::create('order_confirmation_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40); // advance_media | delivery_status | delivery_by
            $table->string('name');
            $table->string('color', 20)->default('secondary');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['type', 'name']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmation_options');
    }
};
