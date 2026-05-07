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
        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'id'], 'wacm_convo_id_index');
            $table->index(['conversation_id', 'created_at'], 'wacm_convo_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_caraka_messages', function (Blueprint $table) {
            $table->dropIndex('wacm_convo_id_index');
            $table->dropIndex('wacm_convo_created_at_index');
        });
    }
};
