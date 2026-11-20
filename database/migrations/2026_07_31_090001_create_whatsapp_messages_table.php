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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();

            $table->string('direction');
            $table->string('wa_message_id')->nullable()->unique();
            $table->string('type')->default('text');
            $table->text('body')->nullable();

            $table->string('media_id')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('template_name')->nullable();

            $table->string('status')->default('queued');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('raw_payload')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index('direction');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
