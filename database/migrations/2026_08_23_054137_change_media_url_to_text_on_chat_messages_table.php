<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Same issue as contacts.profile_picture (see
     * 2026_07_31_140600_change_profile_picture_to_text_on_contacts_table):
     * Messenger/Instagram attachment URLs from Meta's Graph API are long,
     * signed CDN URLs that regularly exceed the 255-char VARCHAR limit
     * "media_url" was created with, causing inbound image/video/document
     * messages to fail to save entirely (1406 Data too long).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE chat_messages MODIFY media_url TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE chat_messages MODIFY media_url VARCHAR(255) NULL');
    }
};
