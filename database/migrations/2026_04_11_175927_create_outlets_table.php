<?php

use App\Models\Pos\Country\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 500)->nullable();
            $table->foreignIdFor(Country::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\Pos\Address\NewDistrict::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\Pos\Address\Area::class)->nullable()->constrained()->nullOnDelete();
            $table->string('google_map_url', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->time('opens');
            $table->time('closes');
            $table->enum('close_day', ['monday', 'tuesday', 'wednesday',
                'thursday', 'friday', 'saturday', 'sunday']);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
