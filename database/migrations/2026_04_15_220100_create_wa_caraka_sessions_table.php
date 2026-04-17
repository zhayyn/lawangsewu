<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_sessions', function (Blueprint $table) {
            $table->id();

            // The remote party's phone number currently in a 2-stage conversation
            $table->string('remote_number', 32);

            // The command they chose in stage 1 (e.g. '7' for cek perkara)
            $table->string('pending_command', 8);

            // The prompt text that was sent to them
            $table->text('prompt_sent')->nullable();

            // Auto-expire stale sessions (default 30 minutes)
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['remote_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_sessions');
    }
};
