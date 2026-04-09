<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guestbook_settings', function (Blueprint $table) {
            $table->string('id', 1)->primary();
            $table->unsignedSmallInteger('per_page')->default(10);
            $table->boolean('require_identity_fields')->default(true);
            $table->string('event_name', 255)->nullable();
            $table->timestamps();
        });

        DB::table('guestbook_settings')->insert([
            'id' => '1',
            'per_page' => 10,
            'require_identity_fields' => true,
            'event_name' => 'Pendopo Pengadilan Agama Semarang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guestbook_settings');
    }
};
