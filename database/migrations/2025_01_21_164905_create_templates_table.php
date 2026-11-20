<?php

use App\Models\Template\TemplateType;
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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->longText('default_content')->nullable();
            $table->longText('custom_content')->nullable();
            $table->enum('type', ['sms', 'mail', 'database']);
            $table->foreignIdFor(TemplateType::class)
                ->index('notification_type_id')
                ->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
