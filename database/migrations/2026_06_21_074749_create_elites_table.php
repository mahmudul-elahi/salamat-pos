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
        Schema::create('elites', function (Blueprint $table) {
            $table->id();
            $table->string('status_name');
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->integer('point')->default(0);
            $table->json('benefits')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elites');
    }
};
