<?php

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
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('lang', 5)->default('en')->after('faq_category_id');
            $table->unsignedBigInteger('group_id')->nullable()->index()->after('lang');
        });

        // Existing rows: group_id = their own id
        DB::table('faqs')
            ->whereNull('group_id')
            ->get()
            ->each(fn ($row) => DB::table('faqs')->where('id', $row->id)->update(['group_id' => $row->id]));
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn(['lang', 'group_id']);
        });
    }
};
