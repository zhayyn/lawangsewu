<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 32)->default('unknown');   // desktop, mobile, tablet
            $table->string('browser', 64)->nullable();               // Chrome, Firefox, etc.
            $table->string('platform', 64)->nullable();              // Windows, macOS, Android, etc.
            $table->string('login_method', 32)->default('google');   // google, manual, credential
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();
            $table->string('session_id', 128)->nullable();
            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
