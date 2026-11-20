<?php

use App\Models\Pos\Care\Care;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_owned_cares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_owned_id')
                ->constrained('pre_owneds')
                ->cascadeOnDelete();
            $table->foreignIdFor(Care::class, 'care_id')
                ->constrained('cares')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_owned_cares');
    }
};
