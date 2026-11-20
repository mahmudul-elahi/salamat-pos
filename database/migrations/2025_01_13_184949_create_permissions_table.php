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
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('label');
                $table->string('group_name');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->foreignIdFor(\App\Models\Role\Role::class)->constrained()->cascadeOnDelete();
                $table->foreignIdFor(\App\Models\Permission\Permission::class)->constrained()->cascadeOnDelete();
                $table->primary(['permission_id', 'role_id']);
                $table->index(['role_id', 'permission_id'], 'role_permission_index');
            });
        }

        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
                $table->foreignIdFor(\App\Models\Role\Role::class)->constrained()->cascadeOnDelete();    
                $table->index(['user_id', 'role_id'], 'user_role_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');

        Schema::enableForeignKeyConstraints();
    }

};
