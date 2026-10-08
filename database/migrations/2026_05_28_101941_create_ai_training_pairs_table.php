<?php

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
        Schema::create('ai_training_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id')->index();
            $table->text('user_prompt')->comment('Pertanyaan atau keluhan asli dari warga');
            $table->text('human_response')->comment('Jawaban yang diketik oleh CS Manusia / Petugas PA');
            $table->string('source')->default('wacaraka')->comment('wacaraka, webchat, dsb');
            $table->enum('status', ['pending', 'learned', 'ignored'])->default('pending')->comment('Status pembelajaran AI');
            $table->boolean('is_sensitive')->default(false)->comment('Flag jika mengandung data pribadi/NIK');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_pairs');
    }
};
