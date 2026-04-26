<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_caraka_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 40)->default('global');
            $table->date('metric_date');
            $table->unsignedInteger('total_messages')->default(0);
            $table->unsignedInteger('inbound_messages')->default(0);
            $table->unsignedInteger('outbound_messages')->default(0);
            $table->unsignedInteger('history_inbound_messages')->default(0);
            $table->unsignedInteger('history_outbound_messages')->default(0);
            $table->unsignedInteger('realtime_inbound_messages')->default(0);
            $table->unsignedInteger('realtime_outbound_messages')->default(0);
            $table->timestamps();

            $table->unique(['scope_key', 'metric_date'], 'wa_caraka_daily_metrics_scope_date_unique');
            $table->index('metric_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_daily_metrics');
    }
};
