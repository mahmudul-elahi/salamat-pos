<?php

use App\Models\Tenant\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news_and_faqs', function (Blueprint $table) {
            if (Schema::hasTable('news')) {
                Schema::table('news', function (Blueprint $table) {
                    if (!Schema::hasColumn('news', 'tenant_id')) {
                        $table->foreignIdFor(Tenant::class)
                            ->nullable()
                            ->after('id')
                            ->index()
                            ->constrained();
                    }
                });
            }

            if (Schema::hasTable('faqs')) {
                Schema::table('faqs', function (Blueprint $table) {
                    if (!Schema::hasColumn('faqs', 'tenant_id')) {
                        $table->foreignIdFor(Tenant::class)
                            ->nullable()
                            ->after('id')
                            ->index()
                            ->constrained();
                    }
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
