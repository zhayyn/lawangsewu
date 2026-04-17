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
        Schema::create('sidang_queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20);
            $table->date('queue_date');
            $table->string('hearing_number', 80);
            $table->string('courtroom', 50)->default('Ruang Sidang 1');
            $table->string('parties', 255)->nullable();
            $table->timestamp('hearing_time')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('sidang_queue_tickets');
    }
};
