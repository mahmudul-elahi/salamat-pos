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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // Folded in here rather than a separate migration - assigned_team_id
        // has a FK to 'teams', which doesn't exist until this migration
        // creates it, so it can't live any earlier (chat_conversations
        // itself already exists by now, from the 120000 rename migration).
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->foreignId('assigned_team_id')->nullable()->after('assigned_agent_id')->constrained('teams')->nullOnDelete();
            $table->string('priority')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_team_id');
            $table->dropColumn('priority');
        });

        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
