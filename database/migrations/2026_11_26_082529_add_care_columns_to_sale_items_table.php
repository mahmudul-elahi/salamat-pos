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
        Schema::table('sale_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('sale_items', 'product_care_id')) {
                $table->unsignedBigInteger('product_care_id')->nullable()->after('sale_type');
            }
            if (! Schema::hasColumn('sale_items', 'pre_owned_care_id')) {
                $table->unsignedBigInteger('pre_owned_care_id')->nullable()->after('product_care_id');
            }
            if (! Schema::hasColumn('sale_items', 'care_amount')) {
                $table->decimal('care_amount', 12, 2)->default(0)->after('pre_owned_care_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $columns = array_filter(
                ['product_care_id', 'pre_owned_care_id', 'care_amount'],
                fn (string $column): bool => Schema::hasColumn('sale_items', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
