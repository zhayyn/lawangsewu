<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('queue_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_group_id')->constrained('service_groups')->cascadeOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('queue_prefix', 10);
            $table->string('numbering_scope', 40)->default('service_daily');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('service_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_service_id')->constrained('queue_services')->cascadeOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('call_label', 120);
            $table->string('display_label', 120);
            $table->string('location_type', 30)->default('loket');
            $table->string('external_ref', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->date('ticket_date');
            $table->string('ticket_number', 30);
            $table->string('ticket_code', 80);
            $table->foreignId('service_id')->constrained('queue_services')->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained('service_counters')->nullOnDelete();
            $table->string('channel', 30)->default('operator');
            $table->string('status', 30)->default('waiting');
            $table->string('customer_name', 255)->nullable();
            $table->string('case_number', 120)->nullable();
            $table->string('case_id_sipp', 120)->nullable();
            $table->string('hearing_id_sipp', 120)->nullable();
            $table->string('room_id_sipp', 120)->nullable();
            $table->json('payload_json')->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('service_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ticket_date', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['ticket_date', 'ticket_code']);
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
        Schema::dropIfExists('service_counters');
        Schema::dropIfExists('queue_services');
        Schema::dropIfExists('service_groups');
    }
};
