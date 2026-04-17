<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_permit_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_reference_id')->nullable()->index();
            $table->string('nip', 64)->nullable()->index();
            $table->string('employee_name')->nullable();
            $table->string('position')->nullable();
            $table->string('rank', 64)->nullable();
            $table->string('study_type', 120)->nullable();
            $table->string('study_program', 160)->nullable();
            $table->string('status', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('source_system', 64)->index();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['source_reference_id', 'source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_permit_requests');
    }
};
