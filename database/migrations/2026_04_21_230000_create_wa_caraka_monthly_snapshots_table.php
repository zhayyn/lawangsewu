<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_monthly_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 50)->default('global');
            $table->string('month_key', 7);
            $table->date('month_start');
            $table->date('month_end');
            $table->unsignedInteger('total_messages')->default(0);
            $table->unsignedInteger('inbound_messages')->default(0);
            $table->unsignedInteger('outbound_messages')->default(0);
            $table->unsignedInteger('history_messages')->default(0);
            $table->unsignedInteger('realtime_messages')->default(0);
            $table->unsignedInteger('active_conversations')->default(0);
            $table->unsignedInteger('unique_contacts')->default(0);
            $table->string('top_operator_name')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('snapshot_taken_at')->nullable();
            $table->timestamps();

            $table->unique(['scope_key', 'month_key']);
            $table->index(['scope_key', 'month_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_monthly_snapshots');
    }
};
