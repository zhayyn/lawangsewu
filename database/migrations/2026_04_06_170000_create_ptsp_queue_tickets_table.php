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
        Schema::create('ptsp_queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20);
            $table->date('queue_date');
            $table->string('service_desk', 30)->default('PTSP-1');
            $table->string('visitor_name', 120)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['queue_date', 'status']);
            $table->unique(['queue_date', 'ticket_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptsp_queue_tickets');
    }
};
