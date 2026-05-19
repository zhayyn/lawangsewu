<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('omni_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('channel', ['whatsapp', 'instagram', 'facebook', 'web']);
            $table->string('channel_identity_id')->comment('Nomor WA, IG Username, FB ID, Web Session');
            $table->string('customer_name')->nullable();
            $table->string('customer_avatar')->nullable();
            $table->enum('status', ['open', 'resolved', 'spam', 'bot'])->default('bot');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['channel', 'channel_identity_id']);
            $table->index('status');
        });

        Schema::create('omni_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('omni_conversations')->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'image', 'document', 'audio', 'interactive'])->default('text');
            $table->text('content')->nullable();
            $table->json('media_payload')->nullable();
            $table->foreignId('sent_by_user')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('omni_messages');
        Schema::dropIfExists('omni_conversations');
    }
};
