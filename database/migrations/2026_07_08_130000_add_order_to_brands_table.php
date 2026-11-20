<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedInteger('order')->nullable()->after('status');
        });

        // Backfill existing brands with a stable order based on current id sequence,
        // so the list has a sensible order before anyone drags anything.
        DB::table('brands')->orderBy('id')->pluck('id')->each(function ($id, $index) {
            DB::table('brands')->where('id', $id)->update(['order' => $index + 1]);
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
