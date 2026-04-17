<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_reference_id')->nullable()->index();
            $table->string('employee_nip', 64)->nullable()->index();
            $table->string('employee_name')->nullable();
            $table->string('leave_type', 100)->nullable();
            $table->text('reason')->nullable();
            $table->string('duration_days', 32)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('satker', 100)->nullable()->index();
            $table->string('source_system', 64)->index();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['source_reference_id', 'source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
