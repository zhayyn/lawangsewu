<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_access_allowlist', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('note')->nullable();
            $table->boolean('auto_activate')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_access_allowlist');
    }
};
