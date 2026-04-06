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
        Schema::create('guestbook_entries', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('name', 120);
            $table->string('position', 120);
            $table->string('institution_category', 50)->nullable();
            $table->string('institution', 160);
            $table->string('purpose', 255);
            $table->timestamp('checkin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guestbook_entries');
    }
};
