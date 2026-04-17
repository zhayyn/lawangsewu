<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_conversation_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wa_caraka_conversation_id')->constrained('wa_caraka_conversations')->cascadeOnDelete();
            $table->string('label', 40);
            $table->string('tone', 20)->default('amber');
            $table->string('note', 255)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'wa_caraka_conversation_id'], 'wa_caraka_marks_user_conversation_unique');
            $table->index(['user_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_conversation_marks');
    }
};
