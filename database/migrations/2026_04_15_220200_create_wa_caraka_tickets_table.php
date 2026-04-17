<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_tickets', function (Blueprint $table) {
            $table->id();

            // Operator assigned to handle this ticket
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Ticket category — unified from wamehehe's separate tables
            $table->enum('type', ['pengaduan', 'konsultasi', 'umum', 'live_konsul']);

            // Who sent this via WA
            $table->string('remote_number', 32);

            // Original message from the public
            $table->text('message');

            // Operator's reply
            $table->text('reply')->nullable();

            // Workflow status
            $table->enum('status', ['open', 'replied', 'sent', 'closed'])->default('open');

            // Attachment (file path for media replies)
            $table->string('attachment_path')->nullable();

            // Timestamps for workflow tracking
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Live konsultasi specific fields
            $table->unsignedInteger('konsul_jenis_id')->nullable();
            $table->timestamp('scheduled_call_at')->nullable();
            $table->text('reject_reason')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index(['type', 'status']);
            $table->index('remote_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_tickets');
    }
};
