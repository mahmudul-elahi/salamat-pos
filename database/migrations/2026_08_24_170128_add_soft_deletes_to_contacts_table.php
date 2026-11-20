<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * email/phone are unique at the DB level - once contacts can be soft
     * deleted, that constraint would block reusing a deleted contact's
     * phone/email for a new one (the "deleted" row is still physically
     * there). Dropped in favor of the app-level Rule::unique() checks in
     * CustomerRequest/SupplierRequest/ContactRequest, which are updated
     * alongside this migration to only consider non-deleted rows.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('contacts_email_unique');
            $table->dropUnique('contacts_phone_unique');
            $table->index('email', 'customer_email');
            $table->index('phone', 'customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('customer_email');
            $table->dropIndex('customer_phone');
            $table->unique('email', 'contacts_email_unique');
            $table->unique('phone', 'contacts_phone_unique');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
