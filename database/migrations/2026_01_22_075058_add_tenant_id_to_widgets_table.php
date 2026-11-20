<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Tenant\Tenant::class)
                ->nullable()
                ->after('id')
                ->constrained('tenants');

        });

        // id column auto increment fix (IMPORTANT)
        DB::statement('
            ALTER TABLE widgets MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        // rollback auto increment (optional but safe)
        DB::statement('
            ALTER TABLE widgets
            MODIFY id BIGINT UNSIGNED NOT NULL
        ');
    }
};
