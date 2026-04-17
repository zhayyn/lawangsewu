<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_messages', function (Blueprint $table) {
            $table->id();

            // Which operator handled this (nullable for inbound before assignment)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Direction: 'inbound' (WA → Lawangsewu) or 'outbound' (Lawangsewu → WA)
            $table->string('direction', 16)->default('outbound');

            // The remote party's phone number (e.g. 628123456789)
            $table->string('remote_number', 32);

            // Our device's phone number (nullable, from runtime)
            $table->string('local_number', 32)->nullable();

            // Message content
            $table->text('message_text')->nullable();
            $table->string('message_type', 24)->default('text'); // text, image, document, audio, etc.

            // Unique message ID from Baileys/WA runtime
            $table->string('wa_message_id', 128)->nullable()->unique();

            // Status: received, sent, delivered, read, failed
            $table->string('status', 24)->default('sent');

            // Conversation grouping (for threading by remote_number)
            $table->string('conversation_id', 64)->nullable();

            // Raw payload from runtime (JSON)
            $table->json('metadata')->nullable();

            // When an operator replied to this inbound message
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('direction');
            $table->index('remote_number');
            $table->index('status');
            $table->index('conversation_id');
            $table->index(['user_id', 'direction']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_messages');
    }
};
