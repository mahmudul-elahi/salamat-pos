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
        Schema::rename('whatsapp_conversations', 'chat_conversations');
        Schema::rename('whatsapp_messages', 'chat_messages');
        Schema::rename('whatsapp_conversation_assignments', 'chat_conversation_assignments');

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('channel')->default('whatsapp')->after('id');
            $table->renameColumn('wa_phone', 'external_id');
            $table->renameColumn('wa_profile_name', 'profile_name');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->index('channel');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->renameColumn('wa_message_id', 'external_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->renameColumn('external_message_id', 'wa_message_id');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->renameColumn('external_id', 'wa_phone');
            $table->renameColumn('profile_name', 'wa_profile_name');
            $table->dropColumn('channel');
        });

        Schema::rename('chat_conversation_assignments', 'whatsapp_conversation_assignments');
        Schema::rename('chat_messages', 'whatsapp_messages');
        Schema::rename('chat_conversations', 'whatsapp_conversations');
    }
};
