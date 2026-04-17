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
        Schema::create('wa_caraka_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sender')->nullable(); // Local phone or user ID
            $table->string('receiver');
            $table->text('message')->nullable();
            $table->string('type')->default('text'); // text, image, document, etc.
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->json('payload')->nullable(); // Raw response from runtime
            $table->timestamps();
            
            $table->index(['sender', 'receiver']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_caraka_logs');
    }
};
