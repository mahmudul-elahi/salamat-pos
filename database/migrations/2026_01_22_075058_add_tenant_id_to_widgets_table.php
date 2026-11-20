<?php

use App\Models\Tenant\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->foreignIdFor(Tenant::class)
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
