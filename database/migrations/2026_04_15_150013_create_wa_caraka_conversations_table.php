<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * wa_caraka_conversations
 *
 * One row per unique remote number (conversation).
 * Tracks: who currently owns/claimed the conversation,
 * its status (open/closed/pending), and operator assignment history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_conversations', function (Blueprint $table) {
            $table->id();

            // Unique conversation identifier (e.g. wa_628123456789)
            $table->string('conversation_id', 64)->unique();

            // The remote WA number for this conversation
            $table->string('remote_number', 32);

            // The display name of the remote party (from WA, if available)
            $table->string('remote_name', 128)->nullable();

            // Conversation status
            // open     = active, accepting replies
            // closed   = marked done by operator
            // pending  = waiting for first reply / unowned
            $table->string('status', 24)->default('pending');

            // The operator who currently "owns" (has claimed) this conversation
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();

            // When this conversation was first claimed
            $table->timestamp('claimed_at')->nullable();

            // When this conversation was last updated (message sent/received)
            $table->timestamp('last_activity_at')->nullable();

            // Count of unread inbound messages (for badge display)
            $table->unsignedSmallInteger('unread_count')->default(0);

            $table->timestamps();

            $table->index('remote_number');
            $table->index('status');
            $table->index('claimed_by');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_conversations');
    }
};
