<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at', 'id'], 'wa_caraka_messages_thread_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->dropIndex('wa_caraka_messages_thread_lookup_index');
        });
    }
};
