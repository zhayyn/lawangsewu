<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('external_uid', 128)->nullable()->index();
            $table->string('nip', 64)->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 64)->nullable();
            $table->string('position')->nullable();
            $table->string('rank', 64)->nullable();
            $table->string('satker_code', 32)->nullable()->index();
            $table->string('satker_name')->nullable();
            $table->string('employment_status', 64)->nullable();
            $table->string('source_system', 64)->index();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
