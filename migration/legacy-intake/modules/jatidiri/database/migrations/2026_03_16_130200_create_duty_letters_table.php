<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_letters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_reference_id')->nullable()->index();
            $table->string('letter_number', 128)->nullable()->index();
            $table->date('letter_date')->nullable();
            $table->text('purpose')->nullable();
            $table->string('destination_agency', 160)->nullable();
            $table->string('destination_city', 120)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('signing_officer', 120)->nullable();
            $table->string('dipa_code', 32)->nullable();
            $table->string('source_system', 64)->index();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['source_reference_id', 'source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_letters');
    }
};
