<?php

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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('company_name', 150)->nullable()->index();
            $table->string('phone', 20)->unique();
            $table->string('secondary_phone', 20)->nullable()->unique();
            $table->string('email', 191)->nullable()->unique();

            $table->string('address')->nullable();

            $table->foreignIdFor(Tenant::class)
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->boolean('status')->default(true)->index();

            $table->foreignId('country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->cascadeOnDelete();
            $table->foreignId('new_district_id')->nullable()->constrained('new_districts')->cascadeOnDelete();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'country_id', 'area_id', 'new_district_id'],
                'suppliers_location_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
