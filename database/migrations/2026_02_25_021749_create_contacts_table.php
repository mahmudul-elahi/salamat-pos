<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique()->index('customer_email');
            $table->string('phone')->nullable()->unique()->index('customer_phone');
            $table->string('secondary_phone')->nullable();
            $table->string('business_name')->nullable();
            $table->foreignIdFor(\App\Models\Pos\Country\Country::class)->nullable()->constrained('countries');
            $table->foreignId('new_district_id')->nullable()
                ->constrained('new_districts');
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas');
            $table->string('address')->nullable();
            $table->string('type');
            $table->enum('status', ['active', 'inactive', 'banned']);
            $table->string('platform_url')->nullable();
            $table->string('business_logo')->nullable();
            $table->string('order_channel')->nullable();
            $table->string('trade_license_number')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
