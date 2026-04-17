<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * wa_caraka_handovers
 *
 * Tracks explicit conversation takeover/handover requests.
 * When operator B wants to take over a conversation claimed by operator A:
 *   1. Operator B sends a takeover request → status: pending
 *   2. Operator A approves or rejects
 *   3. If approved → conversation.claimed_by is updated to B
 *   4. Admins/Superadmins can force takeover (auto_approved = true)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_handovers', function (Blueprint $table) {
            $table->id();

            // The conversation being taken over
            $table->foreignId('conversation_id')->constrained('wa_caraka_conversations')->cascadeOnDelete();

            // Operator requesting the takeover
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            // Current owner who needs to approve (null if force takeover by admin)
            $table->foreignId('requested_to')->nullable()->constrained('users')->nullOnDelete();

            // Status of this handover request
            // pending, approved, rejected, cancelled
            $table->string('status', 24)->default('pending');

            // Optional reason/message from requestor
            $table->string('reason', 512)->nullable();

            // Whether this was a forced takeover by admin (no approval needed)
            $table->boolean('force_approved')->default(false);

            // When the decision was made
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['conversation_id', 'status']);
            $table->index(['requested_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_handovers');
    }
};
