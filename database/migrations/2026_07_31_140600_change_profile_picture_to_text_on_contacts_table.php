<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Messenger/Instagram profile picture URLs from Meta's Graph API are long,
     * signed CDN URLs that regularly exceed the 255-char VARCHAR limit the
     * "profile_picture" column was originally created with.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE contacts MODIFY profile_picture TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE contacts MODIFY profile_picture VARCHAR(255) NULL');
    }
};
