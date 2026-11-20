<?php

use App\Models\Hrm\Beneficiary;
use App\Models\Hrm\Payslip;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Not part of the requested schema, added so each payslip can show a
     * line-by-line breakdown (basic salary, each beneficiary applied,
     * overtime) instead of just a single net total.
     */
    public function up(): void
    {
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payslip::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Beneficiary::class)->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['basic', 'earning', 'deduction']);
            $table->string('label');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
    }
};
