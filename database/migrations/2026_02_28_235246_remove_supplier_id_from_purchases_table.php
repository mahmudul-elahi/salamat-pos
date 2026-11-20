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
//        Schema::table('purchases', function (Blueprint $table) {
//            //drop foreign key first
//            $table->dropForeign(['supplier_id']);
//
//            // drop index if exists
//            $table->dropIndex(['tenant_id', 'supplier_id']);
//
//            // drop column
//            $table->dropColumn('supplier_id');
//        });
//
//        Schema::table('purchase_returns', function (Blueprint $table) {
//            //drop foreign key first
//            $table->dropForeign(['supplier_id']);
//
//            // drop index if exists
//            $table->dropIndex(['tenant_id', 'supplier_id']);
//
//            // drop column
//            $table->dropColumn('supplier_id');
//        });
//
//        Schema::table('sales', function (Blueprint $table) {
//            //drop foreign key first
//            $table->dropForeign(['customer_id']);
//
//            // drop index if exists
//            $table->dropIndex(['tenant_id', 'customer_id']);
//
//            // drop column
//            $table->dropColumn('customer_id');
//        });
//        Schema::table('sale_returns', function (Blueprint $table) {
//            //drop foreign key first
//            $table->dropForeign(['customer_id']);
//
//            // drop index if exists
//            $table->dropIndex(['tenant_id', 'customer_id']);
//
//            // drop column
//            $table->dropColumn('customer_id');
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('purchases', function (Blueprint $table) {
//            $table->foreignId('supplier_id')
//                ->constrained('suppliers')
//                ->cascadeOnUpdate()
//                ->restrictOnDelete();
//
//            $table->index(['tenant_id', 'supplier_id']);
//        });
//
//        Schema::table('purchase_returns', function (Blueprint $table) {
//            $table->foreignId('supplier_id')
//                ->constrained('suppliers')
//                ->cascadeOnUpdate()
//                ->restrictOnDelete();
//
//            $table->index(['tenant_id', 'supplier_id']);
//        });
//
//        Schema::table('sales', function (Blueprint $table) {
//            $table->foreignId('customer_id')
//                ->constrained('customers')
//                ->cascadeOnUpdate()
//                ->restrictOnDelete();
//
//            $table->index(['tenant_id', 'customer_id']);
//        });
//
//        Schema::table('sale_returns', function (Blueprint $table) {
//            $table->foreignId('customer_id')
//                ->constrained('customers')
//                ->cascadeOnUpdate()
//                ->restrictOnDelete();
//
//            $table->index(['tenant_id', 'customer_id']);
//        });
    }
};
