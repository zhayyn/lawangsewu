<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_nip', 64)->index();
            $table->integer('year');
            $table->integer('annual_quota')->default(12);
            $table->integer('used')->default(0);
            $table->integer('remaining')->default(12);
            $table->integer('carryover_from_previous')->default(0);
            $table->text('notes')->nullable();
            $table->string('source_system', 64)->index();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_updated_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['employee_nip', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
