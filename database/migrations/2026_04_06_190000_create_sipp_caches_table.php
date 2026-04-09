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
        Schema::create('sipp_caches', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 100)->unique();
            $table->string('data_type', 50);
            $table->longText('data_content');
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['data_type', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipp_caches');
    }
};
