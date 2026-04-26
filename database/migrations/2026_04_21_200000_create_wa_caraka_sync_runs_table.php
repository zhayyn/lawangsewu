<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_key', 100)->unique();
            $table->string('source', 32)->default('history');
            $table->string('status', 24)->default('running');
            $table->string('connection_jid', 120)->nullable();
            $table->string('device_label', 120)->nullable();
            $table->string('sync_type', 40)->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->unsignedInteger('batches_count')->default(0);
            $table->unsignedInteger('chats_count')->default(0);
            $table->unsignedInteger('contacts_count')->default(0);
            $table->unsignedInteger('messages_received')->default(0);
            $table->unsignedInteger('messages_imported')->default(0);
            $table->unsignedInteger('messages_duplicate')->default(0);
            $table->unsignedInteger('messages_failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_sync_runs');
    }
};
